<?php
require __DIR__ . '/../config/db.php';

echo "<h2>Seeding Database</h2>";

$depts = [
    ['Computer Science & Engineering', 'CSE'],
    ['Information Technology', 'IT'],
    ['Electronics & Communication', 'ECE'],
    ['Mechanical Engineering', 'ME'],
    ['Civil Engineering', 'CE']
];

$stmt = $conn->prepare("INSERT IGNORE INTO departments (name, code) VALUES (?, ?)");
foreach ($depts as $dept) {
    $stmt->bind_param("ss", $dept[0], $dept[1]);
    if ($stmt->execute()) {
        echo "Seeded department: " . $dept[0] . "<br>";
    }
}
$stmt->close();

echo "<h3>Seeding complete.</h3>";
?>