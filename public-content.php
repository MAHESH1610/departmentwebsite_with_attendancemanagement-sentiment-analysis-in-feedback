<?php
header('Content-Type: application/json');

$conn = new mysqli("127.0.0.1", "root", "", "depart_db", 3307);

if ($conn->connect_error) {
    echo json_encode(array(
        "achievements" => array(),
        "events" => array()
    ));
    exit;
}

function ensureColumn($conn, $table, $column, $definition)
{
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE $table ADD COLUMN $column $definition");
    }
}

$conn->query("CREATE TABLE IF NOT EXISTS department_achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS department_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    event_date DATE NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureColumn($conn, "department_achievements", "image_path", "VARCHAR(255)");
ensureColumn($conn, "department_events", "image_path", "VARCHAR(255)");

$achievements = array();
$events = array();

$achievementResult = $conn->query("SELECT title, description, image_path FROM department_achievements ORDER BY achievement_id DESC LIMIT 6");
if ($achievementResult) {
    while ($row = $achievementResult->fetch_assoc()) {
        $achievements[] = $row;
    }
}

$eventResult = $conn->query("SELECT title, event_date, description, image_path
                             FROM department_events
                             WHERE event_date >= CURDATE()
                             ORDER BY event_date ASC, event_id DESC
                             LIMIT 6");
if ($eventResult) {
    while ($row = $eventResult->fetch_assoc()) {
        $events[] = $row;
    }
}

echo json_encode(array(
    "achievements" => $achievements,
    "events" => $events
));

$conn->close();
?>
