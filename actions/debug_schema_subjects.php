<?php
require __DIR__ . '/../config/db.php';
header('Content-Type: text/plain');

echo "--- Subjects Schema ---\n";
$res = $conn->query("DESCRIBE subjects");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>