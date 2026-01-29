<?php
require __DIR__ . '/../config/db.php';

echo "Updating schema...<br>";

// Add columns to subjects if they don't exist
$alter_sql = "
    ALTER TABLE subjects 
    ADD COLUMN IF NOT EXISTS academic_year INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS semester INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS section_id INT DEFAULT NULL;
";

if ($conn->query($alter_sql)) {
    echo "✅ Columns added to subjects table.<br>";
} else {
    echo "❌ Error adding columns: " . $conn->error . "<br>";
}

// Add foreign keys? (Optional, skipping for flexibility/avoiding strict constraint blocks during dev)
// Ideally: FOREIGN KEY (section_id) REFERENCES sections(id)

// Add Index for performance
$index_sql = "ALTER TABLE subjects ADD INDEX IF NOT EXISTS idx_dept_sem_sec (department_id, academic_year, semester, section_id)";
if ($conn->query($index_sql)) {
    echo "✅ Index added.<br>";
}

echo "Done.";
?>