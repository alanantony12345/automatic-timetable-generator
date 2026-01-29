<?php
require __DIR__ . '/../config/db.php';
$res = $conn->query("DESCRIBE subjects");
while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "\n";
}
?>