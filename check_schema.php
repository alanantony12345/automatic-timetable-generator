<?php
require 'c:/xampp/htdocs/autotimetable/config/db.php';
$res = $conn->query("DESCRIBE departments");
while ($row = $res->fetch_assoc())
    print_r($row);
?>