<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.html");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "depart_db", 3307);
$student_id = $_SESSION['student_id'];

$result = $conn->query("SELECT * FROM attendance WHERE student_id='$student_id'");

$present = 0;
$absent = 0;
$od = 0;
$records = array();

while ($row = $result->fetch_assoc()) {
    $records[] = $row;
    if ($row['status'] == 'P') {
        $present++;
    } elseif ($row['status'] == 'OD') {
        $od++;
    } else {
        $absent++;
    }
}

$total = $present + $absent;
$total = $present + $absent + $od;
$percentage = ($total > 0) ? (($present + $od) / $total) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance</title>
    <link rel="stylesheet" href="student.css?v=20260602">
</head>
<body>
    <main class="container">
        <h2>My Attendance</h2>
        <p>Student ID: <?php echo htmlspecialchars($student_id); ?></p>

        <div class="summary-grid">
            <div class="summary-card">
                <strong><?php echo $present; ?></strong>
                <span>Total Present</span>
            </div>
            <div class="summary-card">
                <strong><?php echo $absent; ?></strong>
                <span>Total Absent</span>
            </div>
            <div class="summary-card">
                <strong><?php echo $od; ?></strong>
                <span>Total OD</span>
            </div>
            <div class="summary-card">
                <strong><?php echo round($percentage, 2); ?>%</strong>
                <span>Attendance</span>
            </div>
        </div>

        <table>
            <tr>
                <th>Date</th>
                <th>Period</th>
                <th>Status</th>
            </tr>

            <?php foreach ($records as $row) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['date']); ?></td>
                <td><?php echo htmlspecialchars($row['period']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
            <?php } ?>
        </table>

        <a href="student-dash.php" class="back-btn">Back to Dashboard</a>
    </main>
</body>
</html>
