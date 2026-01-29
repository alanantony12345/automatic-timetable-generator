<?php
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_row()) {
    $table = $row[0];
    $columns = [];
    $col_res = $conn->query("SHOW COLUMNS FROM `$table`");
    while ($col = $col_res->fetch_assoc()) {
        $columns[] = $col;
    }
    $tables[$table] = $columns;
}

echo json_encode($tables, JSON_PRETTY_PRINT);
?>