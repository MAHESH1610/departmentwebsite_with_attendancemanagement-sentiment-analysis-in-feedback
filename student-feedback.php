<?php
session_start();
require_once 'emotion_helpers.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.html");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "depart_db", 3307);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS teacher_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    teacher_name VARCHAR(150) NOT NULL,
    feedback_text TEXT NOT NULL,
    emotion VARCHAR(50) DEFAULT 'Not analyzed',
    video_emotion VARCHAR(50) DEFAULT 'Not analyzed',
    video_path VARCHAR(255),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureTeacherFeedbackEmotionColumn($conn);

$student_id = $_SESSION['student_id'];
$message = "";
$messageClass = "";
const MAX_FEEDBACK_VIDEO_BYTES = 200 * 1024 * 1024;

function uploadErrorMessage($errorCode)
{
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return "The selected video is too large. Please upload a video below 200 MB.";
        case UPLOAD_ERR_PARTIAL:
            return "The video was only partially uploaded. Please try again.";
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
        case UPLOAD_ERR_EXTENSION:
            return "The video could not be uploaded because of a server upload error.";
        default:
            return "The video could not be uploaded. Please try again.";
    }
}

function saveUploadedFile($fieldName, $targetFolder, $allowedTypes)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return array("path" => "", "error" => "");
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return array("path" => "", "error" => uploadErrorMessage($_FILES[$fieldName]['error']));
    }

    if ($_FILES[$fieldName]['size'] > MAX_FEEDBACK_VIDEO_BYTES) {
        return array("path" => "", "error" => "The selected video is too large. Please upload a video below 200 MB.");
    }

    $mimeType = mime_content_type($_FILES[$fieldName]['tmp_name']);
    if (!in_array($mimeType, $allowedTypes, true)) {
        return array("path" => "", "error" => "Please upload a valid video file.");
    }

    if (!is_dir($targetFolder)) {
        mkdir($targetFolder, 0777, true);
    }

    $extension = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
    $fileName = uniqid($fieldName . "_", true) . "." . strtolower($extension);
    $targetPath = $targetFolder . "/" . $fileName;

    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        return array("path" => $targetPath, "error" => "");
    }

    return array("path" => "", "error" => "The video could not be saved. Please try again.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $feedback_text = trim($_POST['feedback_text'] ?? '');
    $upload = saveUploadedFile("feedback_video", "uploads/feedback", array(
        "video/mp4",
        "video/webm",
        "video/ogg",
        "video/quicktime"
    ));
    $video_path = $upload["path"];

    if ($upload["error"] !== "") {
        $message = $upload["error"];
        $messageClass = "error-message";
    } elseif ($teacher_name !== "" && $feedback_text !== "") {
        $emotion = analyzeFeedbackEmotion($feedback_text);
        $video_emotion = analyzeFeedbackVideoEmotion($video_path);

        $stmt = $conn->prepare("INSERT INTO teacher_feedback (student_id, teacher_name, feedback_text, emotion, video_emotion, video_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $student_id, $teacher_name, $feedback_text, $emotion, $video_emotion, $video_path);
        $stmt->execute();
        $stmt->close();

        $message = "Feedback submitted successfully. Text emotion: " . $emotion . ". Video emotion: " . $video_emotion . ".";
        $messageClass = "success-message";
    } else {
        $message = "Please enter teacher name and feedback text.";
        $messageClass = "error-message";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Feedback</title>
    <link rel="stylesheet" href="student.css?v=20260602">
</head>
<body>
    <main class="container">
        <h2>Teacher Feedback</h2>
        <p>Share feedback for a teacher. You can add text and an optional video.</p>

        <?php if ($message !== "") { ?>
            <p class="<?php echo $messageClass; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php } ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MAX_FEEDBACK_VIDEO_BYTES; ?>">
            <input type="text" name="teacher_name" placeholder="Teacher name" required>
            <textarea name="feedback_text" placeholder="Write your feedback" required></textarea>
            <label class="file-label" for="feedback_video">Upload feedback video</label>
            <input id="feedback_video" type="file" name="feedback_video" accept="video/*">
            <button type="submit">Submit Feedback</button>
        </form>

        <a href="student-dash.php" class="back-btn">Back to Dashboard</a>
    </main>
</body>
</html>
