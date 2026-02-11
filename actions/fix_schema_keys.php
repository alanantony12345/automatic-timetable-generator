<?php
require __DIR__ . '/../config/db.php';

// 1. Drop existing Foreign Keys if they rely on the PK? 
// The FKs `faculty_subjects_ibfk_1` reference `faculties(id)`, not the PK of this table.
// So dropping PK is safe regarding FKs.

// 2. Drop Primary Key and Add surrogate ID
// We need to do this carefully.
// Does 'id' exist? NO.

// We will attempt to run raw queries.
$queries = [
    "ALTER TABLE faculty_subjects DROP FOREIGN KEY faculty_subjects_ibfk_1",
    "ALTER TABLE faculty_subjects DROP FOREIGN KEY faculty_subjects_ibfk_2",
    "ALTER TABLE faculty_subjects DROP PRIMARY KEY",
    "ALTER TABLE faculty_subjects ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST",
    "ALTER TABLE faculty_subjects ADD CONSTRAINT faculty_subjects_uniq_alloc UNIQUE (faculty_id, subject_id, section_id)",
    "ALTER TABLE faculty_subjects ADD CONSTRAINT faculty_subjects_ibfk_1 FOREIGN KEY (faculty_id) REFERENCES faculties (id) ON DELETE CASCADE",
    "ALTER TABLE faculty_subjects ADD CONSTRAINT faculty_subjects_ibfk_2 FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE"
];

foreach ($queries as $q) {
    try {
        if ($conn->query($q) === TRUE) {
            echo "Success: $q\n";
        } else {
            // Ignore if key doesn't exist etc.
            echo "Info/Error: " . $conn->error . " | Query: $q\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

echo "\nSchema update attempted. Please verify.\n";
