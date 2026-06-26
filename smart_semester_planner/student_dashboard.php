<?php
session_start();
include "DBconnect.php";

if (!isset($_SESSION['student_name']) || !isset($_SESSION['student_id'])) {
    die("Access denied. Please login.");
}

$student_name = $_SESSION['student_name'];
$student_id = mysqli_real_escape_string($conn, $_SESSION['student_id']);

$query = mysqli_query($conn, "SELECT cgpa, desired_semester_load, no_of_courses FROM STUDENT WHERE id='$student_id'");
$student_data = mysqli_fetch_assoc($query);
$cgpa = $student_data['cgpa'] ?? 'N/A';
$desired_load = $student_data['desired_semester_load'] ?? 'Moderate';
$no_of_courses = $student_data['no_of_courses'] ?? 4;

$check_courses = mysqli_query($conn, "SELECT COUNT(*) as count FROM COURSE_COMPLETED WHERE student_id='$student_id'");
$course_data = mysqli_fetch_assoc($check_courses);
$has_completed_courses = ((int)$course_data['count'] > 0);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #eef2f5; 
            margin: 0; 
            padding: 0; 
            display: flex; 
            flex-direction: column;
            min-height: 100vh; 
        }
        .header-banner { 
            background: linear-gradient(135deg, #2d6cdf, #1b4b9c); 
            color: white; 
            padding: 20px 50px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header-banner h2 { margin: 0; font-size: 24px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        .header-banner p { margin: 0; font-size: 16px; font-weight: bold; opacity: 0.9; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px;}
        .main-content {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }
        .dashboard-card { 
            background: white; 
            width: 100%;
            max-width: 600px;
            padding: 50px;
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            text-align: center;
        }
        .cgpa-container { margin-bottom: 35px; }
        .cgpa-circle { 
            width: 130px; 
            height: 130px; 
            border-radius: 50%; 
            border: 6px solid #2d6cdf; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            font-size: 38px; 
            font-weight: bold; 
            color: #333; 
            margin: 0 auto 15px auto; 
        }
        .cgpa-label { font-size: 16px; color: #777; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;}
        .load-label { margin-top: 10px; color: #555; font-weight: bold; }
        .btn-container { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn { 
            display: block; 
            text-align: center; 
            padding: 16px; 
            border-radius: 8px; 
            font-weight: bold; 
            font-size: 15px; 
            text-decoration: none; 
            transition: all 0.3s ease; 
            border: none; 
            cursor: pointer; 
        }
        .btn-blue { background: #f0f4ff; color: #2d6cdf; border: 2px solid #2d6cdf; }
        .btn-blue:hover { background: #2d6cdf; color: white; }
        .btn-green { background: #28a745; color: white; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2); }
        .btn-green:hover { background: #218838; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3); }
        .btn-orange { background: #ff7a1a; color: white; box-shadow: 0 4px 15px rgba(255, 122, 26, 0.2); }
        .btn-orange:hover { background: #e86d12; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 122, 26, 0.3); }
        .btn-disabled { background: #e0e0e0; color: #999; cursor: not-allowed; }
        @media (max-width: 650px) { .btn-container { grid-template-columns: 1fr; } .header-banner { padding: 18px 20px; flex-direction: column; gap: 8px; align-items: flex-start; } }
    </style>
</head>
<body>
    <div class="header-banner">
        <h2>Welcome, <?php echo htmlspecialchars($student_name); ?>!</h2>
        <p>Student ID: <?php echo htmlspecialchars($student_id); ?></p>
    </div>

    <div class="main-content">
        <div class="dashboard-card">
            <div class="cgpa-container">
                <div class="cgpa-circle"><?php echo htmlspecialchars($cgpa); ?></div>
                <div class="cgpa-label">Current CGPA</div>
                <div class="load-label">Planned Courses: <?php echo htmlspecialchars($no_of_courses); ?></div>
                <div class="load-label">Desired Workload: <?php echo htmlspecialchars($desired_load); ?></div>
            </div>

            <div class="btn-container">
                <a href="choose_completed_courses.php" class="btn btn-blue">Manage Completed Courses</a>
                <?php if ($has_completed_courses): ?>
                    <a href="recommendation.php" class="btn btn-green">Generate Semester Plan</a>
                <?php else: ?>
                    <button class="btn btn-disabled" onclick="alert('You must add completed courses before generating a plan.')">Generate Semester Plan</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
