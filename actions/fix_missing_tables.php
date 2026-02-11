<?php
require __DIR__ . '/../config/db.php';

$tables = [
    "CREATE TABLE IF NOT EXISTS academic_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_name VARCHAR(100) UNIQUE NOT NULL,
        value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        target_audience ENUM('All', 'Faculty', 'Students') DEFAULT 'All',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $sql) {
    if ($conn->query($sql)) {
        echo "Table created/verified successfully.<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// Seed basic academic settings if empty
$check = $conn->query("SELECT COUNT(*) as count FROM academic_settings");
$row = $check->fetch_assoc();
if ($row['count'] == 0) {
    $seeds = [
        ['periods_per_day', '7'],
        ['period_duration', '50'],
        ['working_days', json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])],
        ['break_periods', json_encode([3])]
    ];
    $stmt = $conn->prepare("INSERT INTO academic_settings (key_name, value) VALUES (?, ?)");
    foreach ($seeds as $seed) {
        $stmt->bind_param("ss", $seed[0], $seed[1]);
        $stmt->execute();
    }
    echo "Academic settings seeded.<br>";
}

echo "Database sync complete.";
?>