<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'depart_db', 3307);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error . PHP_EOL);
}

function addColumnIfMissing($conn, $table, $column, $definition)
{
    $safeColumn = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$safeColumn'");

    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

addColumnIfMissing($conn, 'students', 'class_name', 'VARCHAR(80) DEFAULT NULL');
addColumnIfMissing($conn, 'attendance', 'class_name', 'VARCHAR(80) DEFAULT NULL');

echo 'Class columns are ready.' . PHP_EOL;

$conn->close();
?>
