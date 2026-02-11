<?php
require 'c:/xampp/htdocs/autotimetable/config/db.php';
$res = $conn->query("SELECT * FROM departments");
echo "Count: " . $res->num_rows . "\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>