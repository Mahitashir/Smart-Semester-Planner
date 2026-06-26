<?php
session_start();
include "DBconnect.php"; 

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$sid = mysqli_real_escape_string($conn, $_SESSION['student_id']);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$total_sql = "SELECT d.dept_name, SUM(c.credit_hours) AS total
              FROM COURSE c
              JOIN DEPARTMENT d ON c.dept_id = d.dept_id
              GROUP BY d.dept_name
              ORDER BY d.dept_name ASC";
$total_res = mysqli_query($conn, $total_sql);

$stats = [];
while ($row = mysqli_fetch_assoc($total_res)) {
    $stats[$row['dept_name']] = [
        'total' => (int)$row['total'],
        'earned' => 0,
        'percent' => 0
    ];
}

$earned_sql = "SELECT d.dept_name, SUM(c.credit_hours) AS earned
               FROM COURSE_COMPLETED cc
               JOIN COURSE c ON cc.course_id = c.course_id
               JOIN DEPARTMENT d ON c.dept_id = d.dept_id
               WHERE cc.student_id = '$sid'
               GROUP BY d.dept_name";
$earned_res = mysqli_query($conn, $earned_sql);

while ($row = mysqli_fetch_assoc($earned_res)) {
    if (isset($stats[$row['dept_name']])) {
        $stats[$row['dept_name']]['earned'] = (int)$row['earned'];
    }
}

$deficit_rows = [];
foreach ($stats as $dept_name => $data) {
    $percent = ($data['total'] > 0) ? min(100, ($data['earned'] / $data['total']) * 100) : 0;
    $stats[$dept_name]['percent'] = $percent;

    if ($data['earned'] < $data['total']) {
        $deficit_rows[] = [
            'dept_name' => $dept_name,
            'remaining' => $data['total'] - $data['earned'],
            'percent' => $percent
        ];
    }
}

usort($deficit_rows, function ($a, $b) {
    if ($a['percent'] == $b['percent']) {
        return $b['remaining'] <=> $a['remaining'];
    }
    return $a['percent'] <=> $b['percent'];
});

$_SESSION['deficits'] = array_column($deficit_rows, 'dept_name');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Degree Audit</title>
    <style>
        body { font-family: 'Segoe UI', Arial; background: #f5f7fa; padding: 40px; margin: 0; }
        .audit-container { background: white; padding: 30px; border-radius: 15px; max-width: 760px; margin: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        h2 { text-align: center; color: #333; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        .progress-row { margin-bottom: 22px; }
        .bar-bg { background: #e9ecef; height: 12px; border-radius: 6px; margin-top: 7px; overflow: hidden; }
        .bar-fill { background: #28a745; height: 100%; transition: width 0.6s ease; }
        .label-group { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; gap: 12px; }
        .remaining { color: #dc3545; font-size: 13px; margin-top: 5px; }
        .note { background: #e7f3ff; color: #0c5460; padding: 14px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; }
        .btn-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 25px; }
        .btn { display: block; text-align: center; padding: 12px; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
        .btn-back { background: #6c757d; }
        .btn-plan { background: #fd7e14; }
        @media (max-width: 600px) { .btn-row { grid-template-columns: 1fr; } body { padding: 20px; } }
    </style>
</head>
<body>
    <div class="audit-container">
        <h2>Degree Progress Audit</h2>
        <p class="subtitle">Credits earned by department/category</p>

        <div class="note">
            <strong>Audit Note:</strong> Departments with remaining credits are saved as degree-deficit areas. The Balanced Load Plan uses these deficit areas to prioritize courses that help complete the degree faster.
        </div>

        <?php if (empty($stats)): ?>
            <p>No course catalog data found. Please ask an admin to add courses first.</p>
        <?php else: ?>
            <?php foreach($stats as $name => $data): 
                $remaining = max(0, $data['total'] - $data['earned']);
            ?>
                <div class="progress-row">
                    <div class="label-group">
                        <span><?php echo e($name); ?></span>
                        <span><?php echo e($data['earned']); ?> / <?php echo e($data['total']); ?> Cr</span>
                    </div>
                    <div class="bar-bg">
                        <div class="bar-fill" style="width: <?php echo e($data['percent']); ?>%;"></div>
                    </div>
                    <div class="remaining">
                        <?php echo $remaining > 0 ? e($remaining) . ' credit(s) remaining' : 'Completed'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="btn-row">
            <a href="student_dashboard.php" class="btn btn-back">Return to Dashboard</a>
            <a href="load_balance.php" class="btn btn-plan">Open Balanced Load Plan</a>
        </div>
    </div>
</body>
</html>
