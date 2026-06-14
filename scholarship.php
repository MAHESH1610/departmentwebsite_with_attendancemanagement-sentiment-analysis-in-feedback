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

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $scholarshipType = trim($_POST['scholarship_type'] ?? '');
    $marks = (int)($_POST['mark'] ?? 0);
    $attendance = (int)($_POST['attendance'] ?? 0);
    $backlogs = (int)($_POST['backlogs'] ?? 0);
    $income = (int)($_POST['family_income'] ?? 0);
    $sportsOrNot = (int)($_POST['sports'] ?? 0);

    if ($name === '' || $email === '' || $scholarshipType === '') {
        $error = "Please fill all required application details.";
    } else {
        $predictionResult = predictScholarshipStatus($marks, $attendance, $backlogs, $income, $sportsOrNot);

        if (!$predictionResult['ok']) {
            $error = $predictionResult['error'];
        } else {
            $status = $predictionResult['status'];
            $predictionValue = $predictionResult['prediction_value'];

            $stmt = $conn->prepare("INSERT INTO scholarship_applications
                (student_id, name, email, scholarship_type, marks, attendance, backlogs, income, sports_or_not, status, prediction_value)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssssiiiiisi",
                $student_id,
                $name,
                $email,
                $scholarshipType,
                $marks,
                $attendance,
                $backlogs,
                $income,
                $sportsOrNot,
                $status,
                $predictionValue
            );
            $stmt->execute();
            $stmt->close();

            $message = "Application submitted successfully. Your scholarship status is " . $status . ".";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Application</title>
    <link rel="stylesheet" href="scholarship.css?v=20260602">
</head>
<body>
    <main class="container">
        <h2>Scholarship Application</h2>
        <p class="small-text">Student ID: <?php echo htmlspecialchars($student_id); ?></p>

        <?php if ($message !== "") { ?>
            <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
        <?php } ?>

        <?php if ($error !== "") { ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>

        <form method="POST" action="scholarship.php">
            <label for="name">Name</label>
            <input id="name" type="text" name="name" required>

            <label for="email">Email</label>
            <input id="email" type="email" name="email" required>

            <label for="scholarship_type">Scholarship Type</label>
            <select id="scholarship_type" name="scholarship_type" required>
                <option value="">Select scholarship</option>
                <option value="Merit Scholarship">Merit Scholarship</option>
                <option value="Sports Scholarship">Sports Scholarship</option>
                <option value="Financial Aid">Financial Aid</option>
            </select>

            <label for="mark">Marks (%)</label>
            <input id="mark" type="number" name="mark" min="0" max="100" required>

            <label for="attendance">Attendance (%)</label>
            <input id="attendance" type="number" name="attendance" min="0" max="100" required>

            <label for="backlogs">Backlogs</label>
            <input id="backlogs" type="number" name="backlogs" min="0" required>

            <label for="family_income">Family Income</label>
            <input id="family_income" type="number" name="family_income" min="0" required>

            <label for="sports">Sports Participation</label>
            <select id="sports" name="sports" required>
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>

            <button type="submit">Submit Application</button>
        </form>

        <div class="link-button">
            <a href="student-scholarship-status.php" class="back-btn">View My Status</a>
            <a href="student-dash.php" class="back-btn">Back to Dashboard</a>
        </div>
    </main>
</body>
</html>
