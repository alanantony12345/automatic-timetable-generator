<?php
require __DIR__ . '/../config/db.php';
$res = $conn->query("SHOW CREATE TABLE faculty_subjects");
if ($res) {
    $row = $res->fetch_row();
    echo $row[1];
} else {
    echo "Error: " . $conn->error;
}
