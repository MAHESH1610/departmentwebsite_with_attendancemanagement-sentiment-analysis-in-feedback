<?php
$host = '127.0.0.1';
$user = 'root';
$password = '';
$port = 3307;
$schemaFile = __DIR__ . DIRECTORY_SEPARATOR . 'depart_db_schema.sql';

if (!file_exists($schemaFile)) {
    die("Schema file not found: " . $schemaFile . PHP_EOL);
}

$conn = new mysqli($host, $user, $password, '', $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . PHP_EOL);
}

$sql = file_get_contents($schemaFile);

if ($sql === false || trim($sql) === '') {
    die("Schema file is empty or unreadable." . PHP_EOL);
}

if (!$conn->multi_query($sql)) {
    die("Database setup failed: " . $conn->error . PHP_EOL);
}

do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

if ($conn->errno) {
    die("Database setup failed: " . $conn->error . PHP_EOL);
}

$conn->close();

echo "Database depart_db created successfully." . PHP_EOL;
?>
