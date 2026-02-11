<?php
require __DIR__ . '/../config/db.php';

echo "<h1>Manual Entry Insert</h1>";

// 1. Get Latest Version
$res = $conn->query("SELECT id, version_name FROM timetable_versions ORDER BY id DESC LIMIT 1");
if (!$res || $res->num_rows == 0) {
    die("No versions found. Please generate one first.");
}
$version = $res->fetch_assoc();
$version_id = $version['id'];
echo "Target Version: {$version['version_name']} (ID: $version_id)<br>";

// 2. Get Foreign Keys
$sec = $conn->query("SELECT id FROM sections LIMIT 1")->fetch_assoc();
$sub = $conn->query("SELECT id FROM subjects LIMIT 1")->fetch_assoc();
$fac = $conn->query("SELECT id FROM faculties LIMIT 1")->fetch_assoc();
$room = $conn->query("SELECT id FROM classrooms LIMIT 1")->fetch_assoc();

if (!$sec || !$sub || !$fac || !$room) {
    die("Missing basic data (sections, subjects, faculties, or classrooms).");
}

$section_id = $sec['id'];
$subject_id = $sub['id'];
$faculty_id = $fac['id'];
$room_id = $room['id'];

echo "Section: $section_id, Subject: $subject_id, Faculty: $faculty_id, Room: $room_id<br>";

// 3. Insert Entry
$stmt = $conn->prepare("INSERT INTO timetable_entries (version_id, day, period, section_id, subject_id, faculty_id, room_id, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$day = "Monday";
$period = 1;
$type = "Theory";

$stmt->bind_param("isiiiiss", $version_id, $day, $period, $section_id, $subject_id, $faculty_id, $room_id, $type);

if ($stmt->execute()) {
    echo "<h2>SUCCESS: Inserted 1 Entry for Monday Period 1!</h2>";
    echo "Please go to View Timetable and load this version.";
} else {
    echo "<h2>FAILED: " . $stmt->error . "</h2>";
}
?>