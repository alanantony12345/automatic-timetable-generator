<?php
require 'c:/xampp/htdocs/autotimetable/config/db.php';
if ($conn->query("INSERT INTO departments (name, code) VALUES ('Computer Science', 'CS')")) {
    echo "Inserted CS\n";
} else {
    echo "Fail CS: " . $conn->error . "\n";
}
if ($conn->query("INSERT INTO departments (name, code) VALUES ('Electronics', 'EC')")) {
    echo "Inserted EC\n";
} else {
    echo "Fail EC: " . $conn->error . "\n";
}
?>