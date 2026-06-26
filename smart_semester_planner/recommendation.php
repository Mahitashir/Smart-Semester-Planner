<?php
session_start();
include "DBconnect.php";

if (!isset($_SESSION['student_id'])) {
    die("Access denied.");
}

$student_id = mysqli_real_escape_string($conn, $_SESSION['student_id']);
$messages = [];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function difficulty_score($difficulty) {
    $difficulty = strtolower((string)$difficulty);
    if ($difficulty === 'easy' || $difficulty === 'low') return 1;
    if ($difficulty === 'moderate') return 2;
    if ($difficulty === 'difficult' || $difficulty === 'hard' || $difficulty === 'high') return 3;
    return 2;
}

function difficulty_with_score($difficulty) {
    return $difficulty . ' (' . difficulty_score($difficulty) . ')';
}

function valid_course_count($count) {
    $count = (int)$count;
    return $count > 0 ? $count : 4;
}

function credit_limit_from_courses($course_count) {
    return (int)$course_count * 3;
}

function cgpa_credit_cap($cgpa) {
    $cgpa = (float)$cgpa;
    if ($cgpa < 2.50) return 9;
    if ($cgpa < 3.50) return 15;
    return 18;
}

function workload_key($load) {
    $load = strtolower(trim((string)$load));
    if ($load === 'low' || $load === 'easy') return 'low';
    if ($load === 'high' || $load === 'hard' || $load === 'difficult') return 'high';
    return 'moderate';
}

function workload_display_name($load) {
    $key = workload_key($load);
    if ($key === 'low') return 'Easy / Low';
    if ($key === 'high') return 'Hard / High';
    return 'Moderate';
}

function workload_rule_text($load) {
    $key = workload_key($load);
    if ($key === 'low') return 'Easy courses are prioritized more than hard courses.';
    if ($key === 'high') return 'Hard courses are prioritized more than easy courses.';
    return 'Easy and hard courses are balanced as equally as possible.';
}

function workload_pattern($load) {
    $key = workload_key($load);
    if ($key === 'low') return [1, 2, 1, 3, 1, 2];
    if ($key === 'high') return [3, 2, 3, 1, 3, 2];
    return [2, 1, 3, 2, 1, 3];
}

function candidate_priority_compare($a, $b) {
    if ($a['is_deficit_dept'] !== $b['is_deficit_dept']) return $a['is_deficit_dept'] ? -1 : 1;
    if ($a['sort_risk'] !== $b['sort_risk']) return $a['sort_risk'] <=> $b['sort_risk'];
    if ((int)$a['credit_hours'] !== (int)$b['credit_hours']) return (int)$a['credit_hours'] <=> (int)$b['credit_hours'];
    return strcmp($a['course_code'], $b['course_code']);
}

function order_candidates_by_workload($candidates, $load) {
    $buckets = [1 => [], 2 => [], 3 => []];

    foreach ($candidates as $course) {
        $score = (int)$course['difficulty_score'];
        if (!isset($buckets[$score])) {
            $score = 2;
        }
        $buckets[$score][] = $course;
    }

    foreach ($buckets as $score => $items) {
        usort($items, 'candidate_priority_compare');
        $buckets[$score] = $items;
    }

    $pattern = workload_pattern($load);
    $ordered = [];
    $remaining = count($candidates);
    $pattern_index = 0;

    while ($remaining > 0) {
        $preferred_score = $pattern[$pattern_index % count($pattern)];
        $chosen_score = null;

        if (!empty($buckets[$preferred_score])) {
            $chosen_score = $preferred_score;
        } else {
            foreach ([1, 2, 3] as $fallback_score) {
                if (!empty($buckets[$fallback_score])) {
                    $chosen_score = $fallback_score;
                    break;
                }
            }
        }

        if ($chosen_score === null) {
            break;
        }

        $ordered[] = array_shift($buckets[$chosen_score]);
        $remaining--;
        $pattern_index++;
    }

    return $ordered;
}

function risk_level_for_course($course, $dept_stats) {
    $dept_id = $course['dept_id'];
    $difficulty = $course['difficulty_level'];
    $credits = (int)$course['credit_hours'];
    $is_heavy = (strtolower($difficulty) === 'difficult' || $credits >= 4);

    if (!isset($dept_stats[$dept_id])) {
        return [
            'level' => 'New Area',
            'class' => 'risk-new',
            'score' => 2,
            'note' => 'No low grade history in this department, so the system cannot measure department-specific risk yet.'
        ];
    }

    $avg = (float)$dept_stats[$dept_id]['avg_gpa'];

    if ($avg < 2.00) {
        return [
            'level' => 'High Risk',
            'class' => 'risk-high',
            'score' => 5,
            'note' => 'Low CGPA detected in this department. Average GPA: ' . number_format($avg, 2) . '.'
        ];
    }

    if ($avg < 2.70 && $is_heavy) {
        return [
            'level' => 'High Risk',
            'class' => 'risk-high',
            'score' => 4,
            'note' => 'This is a hard course in a weaker department area. Average GPA: ' . number_format($avg, 2) . '.'
        ];
    }

    if ($avg < 2.70) {
        return [
            'level' => 'Medium Risk',
            'class' => 'risk-medium',
            'score' => 3,
            'note' => 'Alert: past performance in this department is below average. Average GPA: ' . number_format($avg, 2) . '.'
        ];
    }

    if ($avg < 3.00 && $is_heavy) {
        return [
            'level' => 'Medium Risk',
            'class' => 'risk-medium',
            'score' => 2,
            'note' => 'Department performance is good but the course is heavy. Average GPA: ' . number_format($avg, 2) . '.'
        ];
    }

    return [
        'level' => 'Low Risk',
        'class' => 'risk-low',
        'score' => 1,
        'note' => 'Past performance in this department looks satisfactory. Average GPA: ' . number_format($avg, 2) . '.'
    ];
}

// -------------------- Student settings --------------------
$student_query = mysqli_query($conn, "SELECT s.*, u.name, u.email
                                      FROM STUDENT s
                                      LEFT JOIN USER u ON s.id = u.id
                                      WHERE s.id='$student_id'");
$student = mysqli_fetch_assoc($student_query);

if (!$student) {
    die("Student profile not found.");
}

$student_name = $student['name'] ?? ($_SESSION['student_name'] ?? 'Student');
$cgpa = $student['cgpa'] ?? 'N/A';
$cgpa_number = (float)$cgpa;
$desired_load = $student['desired_semester_load'] ?? 'Moderate';
$max_courses = valid_course_count($student['no_of_courses'] ?? 4);
$credit_limit = min(credit_limit_from_courses($max_courses), cgpa_credit_cap($cgpa_number));
$workload_name = workload_display_name($desired_load);
$workload_rule = workload_rule_text($desired_load);

// Completed courses
$completed = [];
$completed_ids = [];
$completed_query = mysqli_query($conn, "SELECT cc.course_id, cc.gpa,
                                               c.course_code, c.course_name, c.credit_hours, c.difficulty_level, c.dept_id,
                                               COALESCE(d.dept_name, 'Unknown') AS dept_name
                                        FROM COURSE_COMPLETED cc
                                        JOIN COURSE c ON cc.course_id = c.course_id
                                        LEFT JOIN DEPARTMENT d ON c.dept_id = d.dept_id
                                        WHERE cc.student_id='$student_id'
                                        ORDER BY c.course_code ASC");

while ($row = mysqli_fetch_assoc($completed_query)) {
    $completed[(int)$row['course_id']] = $row;
    $completed_ids[(int)$row['course_id']] = true;
}

// Degree progress audit
$degree_stats = [];
$total_credits_required = 0;
$total_credits_earned = 0;
$deficit_dept_ids = [];
$deficit_dept_names = [];

$total_sql = "SELECT d.dept_id, COALESCE(d.dept_name, 'Unknown') AS dept_name, SUM(c.credit_hours) AS total_credits
              FROM COURSE c
              LEFT JOIN DEPARTMENT d ON c.dept_id = d.dept_id
              GROUP BY d.dept_id, d.dept_name
              ORDER BY d.dept_name ASC";
$total_res = mysqli_query($conn, $total_sql);

while ($row = mysqli_fetch_assoc($total_res)) {
    $dept_id = $row['dept_id'];
    $total = (int)$row['total_credits'];
    $degree_stats[$dept_id] = [
        'dept_id' => $dept_id,
        'dept_name' => $row['dept_name'],
        'total_credits' => $total,
        'earned_credits' => 0,
        'remaining_credits' => $total,
        'percent' => 0
    ];
    $total_credits_required += $total;
}

$earned_sql = "SELECT c.dept_id, SUM(c.credit_hours) AS earned_credits
               FROM COURSE_COMPLETED cc
               JOIN COURSE c ON cc.course_id = c.course_id
               WHERE cc.student_id = '$student_id'
               GROUP BY c.dept_id";
$earned_res = mysqli_query($conn, $earned_sql);

while ($row = mysqli_fetch_assoc($earned_res)) {
    $dept_id = $row['dept_id'];
    if (isset($degree_stats[$dept_id])) {
        $degree_stats[$dept_id]['earned_credits'] = (int)$row['earned_credits'];
    }
}

foreach ($degree_stats as $dept_id => $data) {
    $earned = (int)$data['earned_credits'];
    $total = (int)$data['total_credits'];
    $remaining = max(0, $total - $earned);
    $percent = $total > 0 ? min(100, round(($earned / $total) * 100, 1)) : 0;

    $degree_stats[$dept_id]['remaining_credits'] = $remaining;
    $degree_stats[$dept_id]['percent'] = $percent;
    $total_credits_earned += $earned;

    if ($remaining > 0) {
        $deficit_dept_ids[$dept_id] = true;
        $deficit_dept_names[] = $data['dept_name'];
    }
}

$overall_percent = $total_credits_required > 0 ? min(100, round(($total_credits_earned / $total_credits_required) * 100, 1)) : 0;

//Department-specific performance analytics
$dept_stats = [];
$dept_query = mysqli_query($conn, "SELECT c.dept_id, COALESCE(d.dept_name, 'Unknown') AS dept_name,
                                          COUNT(cc.course_id) AS completed_count,
                                          ROUND(AVG(cc.gpa), 2) AS avg_gpa,
                                          SUM(CASE WHEN cc.gpa < 2.00 THEN 1 ELSE 0 END) AS weak_count
                                   FROM COURSE_COMPLETED cc
                                   JOIN COURSE c ON cc.course_id = c.course_id
                                   LEFT JOIN DEPARTMENT d ON c.dept_id = d.dept_id
                                   WHERE cc.student_id='$student_id'
                                   GROUP BY c.dept_id, d.dept_name
                                   ORDER BY avg_gpa ASC");

while ($row = mysqli_fetch_assoc($dept_query)) {
    $dept_stats[$row['dept_id']] = $row;
}

//Retake management
$retake_threshold = 3.00;
$retake_eligible = [];
foreach ($completed as $course_id => $row) {
    if ((float)$row['gpa'] < $retake_threshold) {
        $retake_eligible[$course_id] = $row;
    }
}

$selected_retake_ids = [];
if (isset($_POST['retake_courses']) && is_array($_POST['retake_courses'])) {
    foreach ($_POST['retake_courses'] as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $selected_retake_ids[$id] = true;
        }
    }
}

$selected_retakes = [];
foreach ($selected_retake_ids as $id => $value) {
    if (isset($completed[$id])) {
        $row = $completed[$id];
        $row['risk'] = [
            'level' => 'Retake Priority',
            'class' => 'risk-retake',
            'score' => 0,
            'note' => 'Previous grade verified: ' . $row['gpa'] . '. Retake is scheduled before new courses.'
        ];
        $selected_retakes[] = $row;
    } else {
        $messages[] = "A retake course was not given because it's not completed.";
    }
}

// -------------------- New course candidate generation --------------------
$where_not_completed = "";
if (!empty($completed_ids)) {
    $id_list = implode(',', array_map('intval', array_keys($completed_ids)));
    $where_not_completed = "WHERE c.course_id NOT IN ($id_list)";
}

$candidate_query = mysqli_query($conn, "SELECT c.course_id, c.course_code, c.course_name, c.credit_hours, c.difficulty_level, c.dept_id,
                                               COALESCE(d.dept_name, 'Unknown') AS dept_name,
                                               GROUP_CONCAT(p.prereq_course_id SEPARATOR ',') AS prereq_ids,
                                               GROUP_CONCAT(pc.course_code SEPARATOR ', ') AS prereq_codes
                                        FROM COURSE c
                                        LEFT JOIN DEPARTMENT d ON c.dept_id = d.dept_id
                                        LEFT JOIN PREREQUISITE p ON c.course_id = p.course_id
                                        LEFT JOIN COURSE pc ON p.prereq_course_id = pc.course_id
                                        $where_not_completed
                                        GROUP BY c.course_id
                                        ORDER BY c.course_code ASC");

$available_candidates = [];
$locked_candidates = [];

while ($course = mysqli_fetch_assoc($candidate_query)) {
    $missing = [];
    $prereq_ids = [];

    if (!empty($course['prereq_ids'])) {
        $prereq_ids = array_filter(array_map('intval', explode(',', $course['prereq_ids'])));
    }

    foreach ($prereq_ids as $pid) {
        if (!isset($completed_ids[$pid])) {
            $missing[] = $pid;
        }
    }

    $risk = risk_level_for_course($course, $dept_stats);
    $course['risk'] = $risk;
    $course['is_deficit_dept'] = isset($deficit_dept_ids[$course['dept_id']]);
    $course['difficulty_score'] = difficulty_score($course['difficulty_level']);
    
    if (empty($missing)) {
        $course['sort_risk'] = $risk['score'];
        $course['sort_difficulty'] = $course['difficulty_score'];
        $available_candidates[] = $course;
    } else {
        $course['missing_count'] = count($missing);
        $locked_candidates[] = $course;
    }
}

//Final workload recommendation plan
$available_candidates = order_candidates_by_workload($available_candidates, $desired_load);

$recommended = [];
$total_credits = 0;
$total_courses = 0;
$difficulty_mix = [1 => 0, 2 => 0, 3 => 0];

foreach ($selected_retakes as $retake) {
    $credits = (int)$retake['credit_hours'];
    $retake['difficulty_score'] = difficulty_score($retake['difficulty_level']);

    if ($total_courses < $max_courses && ($total_credits + $credits) <= $credit_limit) {
        $retake['plan_type'] = 'Retake';
        $recommended[] = $retake;
        $total_credits += $credits;
        $total_courses++;
        $difficulty_mix[(int)$retake['difficulty_score']]++;
    } else {
        $messages[] = $retake['course_code'] . " was selected for retake but credit limit exceeded.";
    }
}

foreach ($available_candidates as $course) {
    $credits = (int)$course['credit_hours'];

    if ($total_courses >= $max_courses) break;
    if (($total_credits + $credits) > $credit_limit) continue;

    $course['plan_type'] = 'New Course';
    $recommended[] = $course;
    $total_credits += $credits;
    $total_courses++;
    $difficulty_mix[(int)$course['difficulty_score']]++;
}

$remaining_courses = max(0, $max_courses - $total_courses);
$remaining_credits = max(0, $credit_limit - $total_credits);
$easy_count = $difficulty_mix[1] ?? 0;
$moderate_count = $difficulty_mix[2] ?? 0;
$hard_count = $difficulty_mix[3] ?? 0;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Smart Semester Recommendation</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; color: #333; }
        .header-banner { background: linear-gradient(135deg, #2d6cdf, #1b4b9c); color: white; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header-banner h2 { margin: 0; font-size: 24px; }
        .header-banner p { margin: 0; font-weight: bold; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; }
        .page { max-width: 1180px; margin: 30px auto; padding: 0 20px 40px 20px; }
        .top-actions { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 11px 18px; border-radius: 8px; text-decoration: none; border: none; cursor: pointer; font-weight: bold; }
        .btn-light { background: white; color: #2d6cdf; border: 1px solid #2d6cdf; }
        .btn-green { background: #28a745; color: white; }
        .btn-orange { background: #ff7a1a; color: white; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .two-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: white; border-radius: 14px; padding: 22px; box-shadow: 0 8px 25px rgba(0,0,0,0.07); margin-bottom: 20px; }
        .small-card h3 { margin: 0 0 8px 0; font-size: 15px; color: #666; }
        .small-card .value { font-size: 26px; font-weight: bold; color: #2d6cdf; }
        h3 { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; }
        th, td { padding: 13px 12px; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
        th { background: #333; color: white; }
        .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: bold; white-space: nowrap; }
        .risk-low { background: #d4edda; color: #155724; }
        .risk-medium { background: #fff3cd; color: #856404; }
        .risk-high { background: #f8d7da; color: #721c24; }
        .risk-new { background: #e2e3e5; color: #383d41; }
        .risk-retake { background: #d1ecf1; color: #0c5460; }
        .degree-badge { background: #ede7f6; color: #4a148c; }
        .note { color: #666; font-size: 13px; line-height: 1.45; }
        .message { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 13px; border-radius: 8px; margin-bottom: 12px; font-weight: bold; }
        .retake-list { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .retake-item { border: 1px solid #ddd; border-radius: 10px; padding: 13px; background: #fafafa; }
        .retake-item label { cursor: pointer; display: block; }
        .retake-item input { margin-right: 8px; }
        .muted { color: #777; }
        .bar-bg { background: #e9ecef; height: 13px; border-radius: 999px; overflow: hidden; margin-top: 8px; }
        .bar-fill { background: #28a745; height: 100%; border-radius: 999px; }
        .progress-row { border-bottom: 1px solid #f0f0f0; padding: 12px 0; }
        .progress-row:last-child { border-bottom: none; }
        .label-group { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; font-weight: bold; }
        .summary-strip { background: #333; color: white; border-radius: 10px; padding: 15px; margin-top: 16px; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .optimizer-note { background: #e7f3ff; color: #0c5460; padding: 14px; border-radius: 8px; margin-bottom: 18px; line-height: 1.5; }
        @media (max-width: 900px) { .grid, .two-grid, .retake-list { grid-template-columns: 1fr; } .header-banner { padding: 18px 20px; flex-direction: column; gap: 8px; align-items: flex-start; } }
    </style>
</head>
<body>
    <div class="header-banner">
        <h2>Smart Semester Recommendation</h2>
        <p><?php echo e($student_name); ?> | ID: <?php echo e($student_id); ?></p>
    </div>

    <div class="page">
        <div class="top-actions">
            <a href="student_dashboard.php" class="btn btn-light">← Back to Dashboard</a>
            <a href="choose_completed_courses.php" class="btn btn-light">Manage Completed Courses</a>
        </div>

        <?php foreach ($messages as $msg): ?>
            <div class="message"><?php echo e($msg); ?></div>
        <?php endforeach; ?>

        <div class="grid">
            <div class="card small-card"><h3>Current CGPA</h3><div class="value"><?php echo e($cgpa); ?></div></div>
            <div class="card small-card"><h3>Desired Workload</h3><div class="value"><?php echo e($workload_name); ?></div></div>
            <div class="card small-card"><h3>Number of Courses</h3><div class="value"><?php echo e($max_courses); ?></div></div>
            <div class="card small-card"><h3>Credit Limit</h3><div class="value"><?php echo e($credit_limit); ?></div></div>
        </div>

        <div class="card">
            <h3>Balanced Load Plan</h3>
            <div class="optimizer-note">
                <strong>Optimizer Note:</strong> Selected workload is <strong><?php echo e($workload_name); ?></strong>.
                <?php echo e($workload_rule); ?> The selected number of courses stays independent from workload.
            </div>
            <table>
                <tr>
                    <th>Code</th>
                    <th>Course Name</th>
                    <th>Department</th>
                    <th>Difficulty</th>
                    <th>Credits</th>
                </tr>
                <?php if (empty($recommended)): ?>
                    <tr><td colspan="5">No balanced load plan could be generated within the current limits.</td></tr>
                <?php else: ?>
                    <?php foreach($recommended as $course): ?>
                    <tr>
                        <td><strong><?php echo e($course['course_code']); ?></strong></td>
                        <td>
                            <?php echo e($course['course_name']); ?>
                            <?php if (!empty($course['is_deficit_dept'])): ?>
                                <br><span class="badge degree-badge">Degree Deficit</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($course['dept_name']); ?></td>
                        <td><?php echo e($course['difficulty_level']); ?></td>
                        <td><?php echo e($course['credit_hours']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
            <div class="summary-strip">
                <span>Recommended Courses: <?php echo e($total_courses); ?> / <?php echo e($max_courses); ?></span>
                <span>Current Total Load: <?php echo e($total_credits); ?> / <?php echo e($credit_limit); ?> Credits</span>
                <span>Difficulty Mix: Easy <?php echo e($easy_count); ?> | Moderate <?php echo e($moderate_count); ?> | Hard <?php echo e($hard_count); ?></span>
            </div>
        </div>

        <div class="card">
            <h3>Degree Progress Audit</h3>
            <p class="muted">This section shows completed credits by department and highlights where credits are still missing.</p>
            <div class="two-grid">
                <div>
                    <div class="progress-row">
                        <div class="label-group">
                            <span>Overall Degree Progress</span>
                            <span><?php echo e($total_credits_earned); ?> / <?php echo e($total_credits_required); ?> Cr</span>
                        </div>
                        <div class="bar-bg"><div class="bar-fill" style="width: <?php echo e($overall_percent); ?>%;"></div></div>
                        <p class="note"><?php echo e($overall_percent); ?>% completed based on the courses currently available in the catalog.</p>
                    </div>
                </div>
                <div>
                    <p class="muted"><strong>Deficit Departments:</strong>
                        <?php echo empty($deficit_dept_names) ? 'No deficit found. All listed department credits are completed.' : e(implode(', ', $deficit_dept_names)); ?>
                    </p>
                    <p class="note">These deficit areas are used by the recommendation engine, so the system can prioritize courses that move the student closer to degree completion.</p>
                </div>
            </div>

            <?php foreach($degree_stats as $dept): ?>
                <div class="progress-row">
                    <div class="label-group">
                        <span><?php echo e($dept['dept_name']); ?></span>
                        <span><?php echo e($dept['earned_credits']); ?> / <?php echo e($dept['total_credits']); ?> Cr</span>
                    </div>
                    <div class="bar-bg"><div class="bar-fill" style="width: <?php echo e($dept['percent']); ?>%;"></div></div>
                    <p class="note">Remaining credits: <?php echo e($dept['remaining_credits']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <?php include "department_risk_analytics.php"; ?>

        <?php include "intelligent_retake_management.php"; ?>

        <div class="card">
            <h3>Recommended Semester Plan</h3>
            <p class="muted">The system first learns about the selected course count and credit limit then generate the difficulty mix based on the selected workload and risk.</p>
            <div class="Selected workload">
                <strong>Selected workload :</strong><strong><?php echo e($workload_name); ?></strong>. 
            </div>
    
            <div class="Workload rule">
                <strong>Workload rule:</strong><strong><?php echo e($workload_rule); ?></strong>. 
            </div>
            <table>
                <tr>
                    <th>Type</th>
                    <th>Course</th>
                    <th>Department</th>
                    <th>Credits</th>
                    <th>Difficulty</th>
                    <th>Priority</th>
                    <th>Risk Flag</th>
                    <th>System Reason</th>
                </tr>
                <?php if (empty($recommended)): ?>
                    <tr><td colspan="8">No course could be recommended within the current course,credit,prerequisite and CGPA.</td></tr>
                <?php else: ?>
                    <?php foreach ($recommended as $course): ?>
                        <tr>
                            <td><strong><?php echo e($course['plan_type']); ?></strong></td>
                            <td><strong><?php echo e($course['course_code']); ?></strong><br><span class="note"><?php echo e($course['course_name']); ?></span></td>
                            <td><?php echo e($course['dept_name']); ?></td>
                            <td><?php echo e($course['credit_hours']); ?></td>
                            <td><?php echo e($course['difficulty_level']); ?></td>
                            <td>
                                <?php if (!empty($course['is_deficit_dept'])): ?>
                                    <span class="badge degree-badge">Degree Deficit</span>
                                <?php else: ?>
                                    <span class="note">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?php echo e($course['risk']['class']); ?>"><?php echo e($course['risk']['level']); ?></span></td>
                            <td class="note"><?php echo e($course['risk']['note']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
            <div class="summary-strip">
                <span>Course selected: <?php echo e($total_courses); ?> / <?php echo e($max_courses); ?> course, <?php echo e($total_credits); ?> / <?php echo e($credit_limit); ?> credits</span>
                <span>Difficulty: Easy course:<?php echo e($easy_count); ?>/Moderate course: <?php echo e($moderate_count); ?>/Hard course:<?php echo e($hard_count); ?></span>
                <span>Remaining: <?php echo e($remaining_courses); ?> courses, <?php echo e($remaining_credits); ?> credits</span>
            </div>
        </div>


        <div class="card">
            <h3>Courses Not Yet Eligible</h3>
            <p class="muted">These courses are not unlocked because one or more prerequisites are still missing.</p>
            <table>
                <tr>
                    <th>Course</th>
                    <th>Department</th>
                    <th>Credits</th>
                    <th>Difficulty</th>
                    <th>Required Prerequisites</th>
                </tr>
                <?php if (empty($locked_candidates)): ?>
                    <tr><td colspan="5">No locked courses found.</td></tr>
                <?php else: ?>
                    <?php foreach ($locked_candidates as $course): ?>
                        <tr>
                            <td><strong><?php echo e($course['course_code']); ?></strong><br><span class="note"><?php echo e($course['course_name']); ?></span></td>
                            <td><?php echo e($course['dept_name']); ?></td>
                            <td><?php echo e($course['credit_hours']); ?></td>
                            <td><?php echo e($course['difficulty_level']); ?></td>
                            <td><?php echo e($course['prereq_codes'] ?: 'None'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>
</body>
</html>
