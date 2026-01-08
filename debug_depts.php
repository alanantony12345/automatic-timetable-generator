<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'c:/xampp/htdocs/autotimetable/config/db.php';
echo "DB: " . $dbname . "\n";
$res = $conn->query('SELECT * FROM departments');
if ($res) {
    echo "Count: " . $res->num_rows . "\n";
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query Error: " . $conn->error . "\n";
}
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_row()) {
    echo "Table: " . $row[0] . "\n";
}
?>