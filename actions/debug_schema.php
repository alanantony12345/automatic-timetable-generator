<?php
require __DIR__ . '/../config/db.php';
$res = $conn->query("DESCRIBE departments");
while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "<br>";
}
echo "<hr>";
$res = $conn->query("SELECT * FROM departments LIMIT 1");
print_r($res->fetch_assoc());
?>