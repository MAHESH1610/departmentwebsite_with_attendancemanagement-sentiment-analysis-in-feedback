<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.html");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "depart_db", 3307);
$student_id = $_SESSION['student_id'];
$message = "";
$messageClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];

    $check = $conn->query("SELECT * FROM students
                           WHERE student_id='$student_id'
                           AND password='$old'");

    if ($check->num_rows > 0) {
        $conn->query("UPDATE students
                      SET password='$new'
                      WHERE student_id='$student_id'");
        $message = "Password updated successfully.";
        $messageClass = "success-message";
    } else {
        $message = "Wrong old password.";
        $messageClass = "error-message";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="student.css?v=20260602">
</head>
<body>
    <main class="container">
        <h2>Change Password</h2>
        <p>Update the password for your student account.</p>

        <?php if ($message !== "") { ?>
            <p class="<?php echo $messageClass; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php } ?>

        <form method="POST">
            <input type="password" name="old_password" placeholder="Old Password" required>
            <input type="password" name="new_password" placeholder="New Password" required>
            <button type="submit">Update Password</button>
        </form>

        <a href="student-dash.php" class="back-btn">Back to Dashboard</a>
    </main>
</body>
</html>
