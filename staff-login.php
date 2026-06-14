<?php

$conn = new mysqli("127.0.0.1", "root", "", "depart_db", 3307);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$staff_id = $_POST['staff_id'] ?? '';
$password = $_POST['password'] ?? '';

$sql = "SELECT * FROM staff 
        WHERE staff_id='$staff_id' 
        AND password='$password'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    header("Location: staff.php");
} else {
    header("Location: invalid.html");
}

$conn->close();
?>
