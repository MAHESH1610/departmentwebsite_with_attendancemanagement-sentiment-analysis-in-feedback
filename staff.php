<?php
$conn = new mysqli("127.0.0.1", "root", "", "depart_db", 3307);

$classOptions = array(
    "1st Year MCA",
    "2nd Year MCA",
    "1st Year MSc CS",
    "2nd Year MSc CS",
    "1st Year MSc IT",
    "2nd Year MSc IT"
);

function ensureColumn($conn, $table, $column, $definition)
{
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE $table ADD COLUMN $column $definition");
    }
}

$conn->query("CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    class_name VARCHAR(80) DEFAULT NULL,
    department VARCHAR(120) DEFAULT 'Computer Science',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
ensureColumn($conn, "students", "class_name", "VARCHAR(80) DEFAULT NULL");

$selectedClass = trim($_GET['class_name'] ?? '');
$result = false;

if (in_array($selectedClass, $classOptions, true)) {
    $stmt = $conn->prepare("SELECT student_id, name FROM students WHERE class_name = ? ORDER BY name ASC, student_id ASC");
    $stmt->bind_param("s", $selectedClass);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Attendance</title>
    <link rel="stylesheet" href="staff-dash.css?v=20260602">
</head>
<body>
    <main class="container">
        <h1>Mark Attendance</h1>
        <p>Choose a class, date, and period, then mark each student as present or absent.</p>

        <div class="page-actions">
            <a href="staff-scholarship-applications.php" class="btn">View Scholarship Applications</a>
            <a href="index.html" class="back-btn">Back to Home</a>
        </div>

        <form method="GET" class="class-filter">
            <select name="class_name" required>
                <option value="">Select Class</option>
                <?php foreach ($classOptions as $classOption) { ?>
                    <option value="<?php echo htmlspecialchars($classOption); ?>" <?php echo ($selectedClass === $classOption) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($classOption); ?>
                    </option>
                <?php } ?>
            </select>
            <button type="submit">Show Students</button>
        </form>

        <?php if ($selectedClass !== '' && !in_array($selectedClass, $classOptions, true)) { ?>
            <p class="error-message">Please select a valid class.</p>
        <?php } ?>

        <?php if ($result) { ?>
        <form action="save-attendance.php" method="POST">
            <input type="hidden" name="class_name" value="<?php echo htmlspecialchars($selectedClass); ?>">
            <div class="attendance-controls">
                <input type="date" name="date" required>
                <select name="period" required>
                    <option value="">Select Period</option>
                    <option value="1">Hour 1</option>
                    <option value="2">Hour 2</option>
                    <option value="3">Hour 3</option>
                    <option value="4">Hour 4</option>
                    <option value="5">Hour 5</option>
                    <option value="6">Hour 6</option>
                </select>
            </div>

            <table>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Present</th>
                    <th>Absent</th>
                </tr>

                <?php if ($result->num_rows > 0) { ?>
                <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td>
                        <input type="radio"
                               name="attendance[<?php echo htmlspecialchars($row['student_id']); ?>]"
                               value="P"
                               required>
                    </td>
                    <td>
                        <input type="radio"
                               name="attendance[<?php echo htmlspecialchars($row['student_id']); ?>]"
                               value="A">
                    </td>
                </tr>
                <?php } ?>
                <?php } else { ?>
                <tr>
                    <td colspan="4">No students found in <?php echo htmlspecialchars($selectedClass); ?>.</td>
                </tr>
                <?php } ?>
            </table>

            <?php if ($result->num_rows > 0) { ?>
                <button type="submit">Submit Attendance</button>
            <?php } ?>
        </form>
        <?php } ?>
    </main>
</body>
</html>
