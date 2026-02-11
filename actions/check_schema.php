<?php
require __DIR__ . '/../config/db.php';
$res = $conn->query("DESCRIBE faculty_subjects");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "<br>";
}
?>