<?php
require_once 'scholarship_helpers.php';

$conn = getDbConnection();
ensureScholarshipApplicationsTable($conn);

$result = $conn->query('SELECT * FROM scholarship_applications ORDER BY applied_at DESC');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Applications</title>
    <link rel="stylesheet" href="scholarship.css?v=20260602">
</head>
<body>
<div class="container">
    <h2>All Scholarship Applications</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <table class="status-table">
            <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Name</th>
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
                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
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
        <p>No scholarship applications found.</p>
    <?php endif; ?>

    <div class="link-button">
        <a href="staff.php" class="back-btn">Back to Staff Panel</a>
    </div>
</div>
</body>
</html>
