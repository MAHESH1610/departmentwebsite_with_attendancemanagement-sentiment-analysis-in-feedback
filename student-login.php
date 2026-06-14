<?php
session_start();

$conn = new mysqli("127.0.0.1", "root", "", "depart_db", 3307);

$student_id = $_POST['student_id'];
$password = $_POST['password'];

$sql = "SELECT * FROM students 
        WHERE student_id='$student_id' 
        AND password='$password'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $_SESSION['student_id'] = $student_id;   // ✅ STORE LOGIN
    header("Location: student-dash.php");
} else {
    header("Location: invalid.html");
}
?>
