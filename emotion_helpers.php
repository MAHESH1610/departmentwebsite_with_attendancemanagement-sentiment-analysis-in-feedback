<?php
function ensureTeacherFeedbackEmotionColumn($conn)
{
    $columnCheck = $conn->query("SHOW COLUMNS FROM teacher_feedback LIKE 'emotion'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE teacher_feedback ADD COLUMN emotion VARCHAR(50) DEFAULT 'Not analyzed'");
    }

    $videoColumnCheck = $conn->query("SHOW COLUMNS FROM teacher_feedback LIKE 'video_emotion'");
    if ($videoColumnCheck && $videoColumnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE teacher_feedback ADD COLUMN video_emotion VARCHAR(50) DEFAULT 'Not analyzed'");
    }
}

function analyzeFeedbackEmotion($feedbackText)
{
    $pythonCandidates = array(
        "C:\\Users\\jayam\\anaconda3\\python.exe",
        "python"
    );
    $scriptPath = __DIR__ . DIRECTORY_SEPARATOR . "mlmodel" . DIRECTORY_SEPARATOR . "predict_emotion.py";

    foreach ($pythonCandidates as $pythonPath) {
        $command = escapeshellarg($pythonPath) . " " . escapeshellarg($scriptPath) . " " . escapeshellarg($feedbackText);
        $output = trim((string)shell_exec($command));

        if (preg_match('/^(angry|disgust|fear|happy|neutral|sad|surprise)$/i', $output)) {
            return strtolower($output);
        }
    }

    return "Not analyzed";
}

function analyzeFeedbackVideoEmotion($videoPath)
{
    if ($videoPath === "") {
        return "Not analyzed";
    }

    $pythonCandidates = array(
        "C:\\Users\\jayam\\anaconda3\\python.exe",
        "python"
    );
    $scriptPath = __DIR__ . DIRECTORY_SEPARATOR . "mlmodel" . DIRECTORY_SEPARATOR . "predict_video_emotion.py";

    foreach ($pythonCandidates as $pythonPath) {
        $command = escapeshellarg($pythonPath) . " " . escapeshellarg($scriptPath) . " " . escapeshellarg($videoPath);
        $output = trim((string)shell_exec($command));

        if (preg_match('/^(angry|disgust|fear|happy|neutral|sad|surprise)$/i', $output)) {
            return strtolower($output);
        }
    }

    return "Not analyzed";
}

function backfillFeedbackEmotions($conn, $limit = 30)
{
    $limit = (int)$limit;
    $result = $conn->query("SELECT feedback_id, feedback_text
                            FROM teacher_feedback
                            WHERE emotion IS NULL OR emotion = '' OR emotion = 'Not analyzed'
                            ORDER BY submitted_at DESC
                            LIMIT $limit");

    if (!$result) {
        return;
    }

    while ($row = $result->fetch_assoc()) {
        $emotion = analyzeFeedbackEmotion($row['feedback_text']);

        if ($emotion === "Not analyzed") {
            continue;
        }

        $stmt = $conn->prepare("UPDATE teacher_feedback SET emotion = ? WHERE feedback_id = ?");
        $stmt->bind_param("si", $emotion, $row['feedback_id']);
        $stmt->execute();
        $stmt->close();
    }
}

function backfillFeedbackVideoEmotions($conn, $limit = 10)
{
    $limit = (int)$limit;
    $result = $conn->query("SELECT feedback_id, video_path
                            FROM teacher_feedback
                            WHERE video_path IS NOT NULL
                              AND video_path <> ''
                              AND (video_emotion IS NULL OR video_emotion = '' OR video_emotion = 'Not analyzed')
                            ORDER BY submitted_at DESC
                            LIMIT $limit");

    if (!$result) {
        return;
    }

    while ($row = $result->fetch_assoc()) {
        $videoEmotion = analyzeFeedbackVideoEmotion($row['video_path']);

        if ($videoEmotion === "Not analyzed") {
            continue;
        }

        $stmt = $conn->prepare("UPDATE teacher_feedback SET video_emotion = ? WHERE feedback_id = ?");
        $stmt->bind_param("si", $videoEmotion, $row['feedback_id']);
        $stmt->execute();
        $stmt->close();
    }
}
?>
