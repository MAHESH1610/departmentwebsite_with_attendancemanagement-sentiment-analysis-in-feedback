<?php
$conn = new mysqli("127.0.0.1", "root", "", "depart_db", 3307);

$date = $_POST['date'] ?? '';
$period = $_POST['period'] ?? '';
$className = $_POST['class_name'] ?? '';
$attendance = $_POST['attendance'] ?? array();

$conn->query("CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    period VARCHAR(10) NOT NULL,
    status VARCHAR(10) NOT NULL,
    class_name VARCHAR(80) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$columnCheck = $conn->query("SHOW COLUMNS FROM attendance LIKE 'class_name'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE attendance ADD COLUMN class_name VARCHAR(80) DEFAULT NULL");
}

foreach ($attendance as $student_id => $status) {
    $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, period, status, class_name)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $student_id, $date, $period, $status, $className);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Saved</title>
    <link rel="stylesheet" href="save-attendance.css?v=20260602">
</head>
<body>
    <div class="box">
        <h2>Attendance Saved</h2>
        <p><?php echo htmlspecialchars($className); ?> - Period <?php echo htmlspecialchars($period); ?> completed successfully.</p>
        <a href="staff.php?class_name=<?php echo urlencode($className); ?>" class="back-btn">Back to Class Attendance</a>
    </div>
</body>
</html>
