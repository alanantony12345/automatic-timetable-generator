-- Database Updates v2

-- 1. Academic Settings Table
CREATE TABLE IF NOT EXISTS academic_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Announcements Table
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target_audience ENUM('All', 'Faculty', 'Students') DEFAULT 'All',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Update faculty_subjects for better mapping
CREATE TABLE IF NOT EXISTS faculty_subjects (
    faculty_id INT,
    subject_id INT,
    PRIMARY KEY (faculty_id, subject_id),
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- We need to check if columns exist before adding them to avoid errors if run multiple times.
-- Since MySQL/MariaDB < 10.2 doesn't support IF EXISTS in ALTER TABLE easily, we will just try to add them.
-- If this script fails, it might be because columns already exist.

-- Add section_id to map into specific sections
ALTER TABLE faculty_subjects ADD COLUMN IF NOT EXISTS section_id INT;
ALTER TABLE faculty_subjects ADD COLUMN IF NOT EXISTS weekly_hours INT DEFAULT 4;
ALTER TABLE faculty_subjects ADD COLUMN IF NOT EXISTS subject_type ENUM('Theory', 'Lab') DEFAULT 'Theory';

-- Foreign key for section_id (optional, but good for integrity)
-- ALTER TABLE faculty_subjects ADD CONSTRAINT fk_fs_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL;

-- 4. Lab constraints (Optional but requested)
CREATE TABLE IF NOT EXISTS lab_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT,
    batch_size INT DEFAULT 30,
    has_batches TINYINT(1) DEFAULT 0, -- If true, split into A/B
    consecutive_periods INT DEFAULT 2,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- 5. Timetable Versions (Backup/Version Control)
CREATE TABLE IF NOT EXISTS timetable_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_name VARCHAR(100),
    data_json LONGTEXT, -- Stores the entire timetable structure as JSON
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. User Status
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;
