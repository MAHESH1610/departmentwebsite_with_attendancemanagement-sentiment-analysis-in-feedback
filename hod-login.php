<?php
session_start();

$conn = new mysqli("127.0.0.1", "root", "", "depart_db", 3307);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS hod_users (
    hod_id VARCHAR(50) PRIMARY KEY,
    password VARCHAR(100) NOT NULL,
    name VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("INSERT IGNORE INTO hod_users (hod_id, password, name)
              VALUES ('hod', 'hod123', 'Head of Department')");

$hod_id = $_POST['hod_id'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT name FROM hod_users WHERE hod_id = ? AND password = ?");
$stmt->bind_param("ss", $hod_id, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $_SESSION['hod_id'] = $hod_id;
    $_SESSION['hod_name'] = $row['name'];
    header("Location: hod-dashboard.php");
} else {
    header("Location: invalid.html");
}

$stmt->close();
$conn->close();
?>
