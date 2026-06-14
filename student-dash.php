<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.html");
    exit;
}

$student_id = $_SESSION['student_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="student.css?v=20260602">
</head>
<body>
    <main class="container">
        <h2>Welcome <?php echo htmlspecialchars($student_id); ?></h2>
        <p>Access attendance, scholarship updates, password settings, and account actions.</p>

        <a href="view-attendance.php" class="btn">View Attendance</a>
        <a href="scholarship.php" class="btn">Apply Scholarship</a>
        <a href="student-scholarship-status.php" class="btn">Scholarship Status</a>
        <a href="student-feedback.php" class="btn">Teacher Feedback</a>
        <a href="change-password.php" class="btn">Change Password</a>
        <a href="logout.php" class="btn logout">Logout</a>
    </main>
</body>
</html>
