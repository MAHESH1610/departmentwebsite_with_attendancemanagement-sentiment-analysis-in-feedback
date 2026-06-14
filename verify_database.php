<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'depart_db', 3307);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error . PHP_EOL);
}

$tables = $conn->query('SHOW TABLES');

echo 'Tables:' . PHP_EOL;
while ($row = $tables->fetch_array()) {
    echo '- ' . $row[0] . PHP_EOL;
}

foreach (array('students', 'staff', 'hod_users') as $table) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
    $row = $result->fetch_assoc();
    echo $table . '=' . $row['total'] . PHP_EOL;
}

$conn->close();
?>
