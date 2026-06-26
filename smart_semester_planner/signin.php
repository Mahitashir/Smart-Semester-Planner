<?php
session_start();
include "DBconnect.php";

$role = $_POST['role'] ?? "";

// clear old errors
unset($_SESSION['id_error']);
unset($_SESSION['email_error']);


$id = $_POST['id'];
$name = $_POST['name'];
$email = $_POST['email'];

$valid = true;


if (!preg_match("/^\d{8}$/", $id)) {
    $_SESSION['id_error'] = ucfirst($role) . " ID must be 8 digits";
    $valid = false;
}


if ($role === "student" && !preg_match("/^[a-zA-Z0-9._%+-]+@g\.bracu\.ac\.bd$/", $email)) {
    $_SESSION['email_error'] = "Email must end with @g.bracu.ac.bd";
    $valid = false;
}


if (!$valid) {
    header("Location: login.php");
    exit();
}


$query_user = "INSERT IGNORE INTO USER (id, name, email) VALUES ('$id', '$name', '$email')";
mysqli_query($conn, $query_user);



if ($role === "student") {

    
    $check_student = mysqli_query($conn, "SELECT * FROM STUDENT WHERE id='$id'");
    
    if(mysqli_num_rows($check_student) > 0) {
        $cgpa = $_POST['cgpa'];
        $load = $_POST['course_load'];
        $no_of_courses = intval($_POST['no_of_courses']);

        if ($no_of_courses < 1) {
            $no_of_courses = 4;
        }

        mysqli_query($conn, "UPDATE STUDENT SET cgpa='$cgpa', no_of_courses='$no_of_courses', desired_semester_load='$load' WHERE id='$id'");

        $_SESSION['student_name'] = $name;
        $_SESSION['student_id'] = $id;
        header("Location: student_dashboard.php");
        exit();
    } else {
      
        $cgpa = $_POST['cgpa'];
        $load = $_POST['course_load'];
        $no_of_courses = intval($_POST['no_of_courses']);

        if ($no_of_courses < 1) {
            $no_of_courses = 4;
        }

        $query_student = "INSERT INTO STUDENT (id, no_of_courses, cgpa, desired_semester_load) 
                          VALUES ('$id', '$no_of_courses', '$cgpa', '$load')";
        
        if(mysqli_query($conn, $query_student)) {
            $_SESSION['student_name'] = $name;
            $_SESSION['student_id'] = $id;
            header("Location: student_dashboard.php");
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }

} elseif ($role === "admin") {
    
    $password = $_POST['password'];

 
    $check_admin = mysqli_query($conn, "SELECT * FROM ADMIN WHERE id='$id'");
    
    if(mysqli_num_rows($check_admin) > 0) {
       
        $_SESSION['admin_name'] = $name;
        $_SESSION['admin_id'] = $id;
        header("Location: admin_dashboard.php");
        exit();
    } else {

        $query_admin = "INSERT INTO ADMIN (id, password) VALUES ('$id', '$password')";
        
        if(mysqli_query($conn, $query_admin)) {
            $_SESSION['admin_name'] = $name;
            $_SESSION['admin_id'] = $id;
            header("Location: admin_dashboard.php");
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }

} else {
    echo "Invalid Role!";
}
?>