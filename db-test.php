<?php

require_once __DIR__ . '/includes/database.php';

$result = $pdo->query("SELECT COUNT(*) AS role_count FROM roles");
$row = $result->fetch();

header('Content-Type: text/plain');

echo "Database connection successful\n";
echo "Roles found: " . $row['role_count'] . "\n";
