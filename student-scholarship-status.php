<?php
session_start();
require_once 'scholarship_helpers.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student-login.html');
    exit;
}

$student_id = $_SESSION['student_id'];
$conn = getDbConnection();
ensureScholarshipApplicationsTable($conn);

$stmt = $conn->prepare('SELECT application_id, scholarship_type, marks, attendance, backlogs, income, sports_or_not, status, applied_at FROM scholarship_applications WHERE student_id = ? ORDER BY applied_at DESC');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Scholarship Applications</title>
    <link rel="stylesheet" href="scholarship.css?v=20260602">
</head>
<body>
<div class="container">
    <h2>My Scholarship Applications</h2>
    <p class="small-text">Student ID: <?php echo htmlspecialchars($student_id); ?></p>

    <?php if ($result->num_rows > 0): ?>
        <table class="status-table">
            <tr>
                <th>#</th>
                <th>Scholarship</th>
                <th>Marks</th>
                <th>Attendance</th>
                <th>Backlogs</th>
                <th>Income</th>
                <th>Sports</th>
                <th>Status</th>
                <th>Applied At</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['application_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['scholarship_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['marks']); ?>%</td>
                    <td><?php echo htmlspecialchars($row['attendance']); ?>%</td>
                    <td><?php echo htmlspecialchars($row['backlogs']); ?></td>
                    <td><?php echo htmlspecialchars($row['income']); ?></td>
                    <td><?php echo ((int)$row['sports_or_not'] === 1) ? 'Yes' : 'No'; ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['applied_at']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No scholarship applications found yet. Submit your application on <a href="scholarship.php">the scholarship page</a>.</p>
    <?php endif; ?>

    <div class="link-button">
        <a href="student-dash.php" class="back-btn">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
