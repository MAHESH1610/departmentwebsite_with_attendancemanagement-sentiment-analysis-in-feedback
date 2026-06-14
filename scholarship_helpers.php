<?php
function getDbConnection()
{
    $conn = new mysqli('127.0.0.1', 'root', '', 'depart_db', 3307);
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    return $conn;
}

function ensureScholarshipApplicationsTable($conn)
{
    $conn->query("CREATE TABLE IF NOT EXISTS scholarship_applications (
        application_id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL,
        scholarship_type VARCHAR(100) NOT NULL,
        marks INT NOT NULL,
        attendance INT NOT NULL,
        backlogs INT NOT NULL,
        income BIGINT NOT NULL,
        sports_or_not TINYINT NOT NULL DEFAULT 0,
        status VARCHAR(50) NOT NULL,
        prediction_value TINYINT NULL,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    ensureScholarshipColumn($conn, 'sports_or_not', 'TINYINT NOT NULL DEFAULT 0');
    ensureScholarshipColumn($conn, 'prediction_value', 'TINYINT NULL');
}

function ensureScholarshipColumn($conn, $column, $definition)
{
    $safeColumn = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM scholarship_applications LIKE '$safeColumn'");

    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE scholarship_applications ADD COLUMN $column $definition");
    }
}

function predictScholarshipStatus($marks, $attendance, $backlogs, $income, $sportsOrNot)
{
    $payload = json_encode(array(
        'mark' => $marks,
        'attendance' => $attendance,
        'backlogs' => $backlogs,
        'family_income' => $income,
        'sports_or_not' => $sportsOrNot
    ));

    $ch = curl_init('http://127.0.0.1:5001/predict');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);

    $response = curl_exec($ch);
    if ($response === false) {
        curl_close($ch);
        return predictScholarshipStatusFallback($marks, $attendance, $backlogs, $income, $sportsOrNot);
    }

    curl_close($ch);
    $result = json_decode($response, true);

    if (!is_array($result) || !isset($result['prediction'])) {
        return predictScholarshipStatusFallback($marks, $attendance, $backlogs, $income, $sportsOrNot);
    }

    $predictionValue = (int)$result['prediction'];
    $status = $result['status'] ?? ($predictionValue === 1 ? 'Eligible' : 'Not Eligible');

    return array(
        'ok' => true,
        'prediction_value' => $predictionValue,
        'status' => $status
    );
}

function predictScholarshipStatusFallback($marks, $attendance, $backlogs, $income, $sportsOrNot)
{
    $predictionValue = (int)(
        (int)$marks >= 80
        && (int)$attendance >= 85
        && (int)$backlogs === 0
        && (int)$income <= 300000
    );

    if ((int)$sportsOrNot === 1 && (int)$marks >= 60 && (int)$attendance >= 75 && (int)$backlogs <= 1) {
        $predictionValue = 1;
    }

    return array(
        'ok' => true,
        'prediction_value' => $predictionValue,
        'status' => $predictionValue === 1 ? 'Eligible' : 'Not Eligible'
    );
}
?>
