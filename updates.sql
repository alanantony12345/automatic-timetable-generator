-- Ensure base tables exist first (from database.sql)
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(10) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE,
    department_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    department_id INT,
    designation VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    capacity INT,
    type ENUM('Lab', 'Lecture') DEFAULT 'Lecture',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT,
    academic_year VARCHAR(20),
    semester INT,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Now apply updates
-- Add columns to subjects table
-- Using stored procedure or block to check if column exists is complex in raw SQL script for imports
-- simpler to just try ALTER and if it fails due to duplicate column, it's fine (but we want to avoid error stopping script)
-- For MariaDB/MySQL 5.7+ IF NOT EXISTS for ADD COLUMN is supported in some versions, but not all.
-- We will try the standard ADD COLUMN. If it fails because column exists, we might need a better approach, but for now assuming clean or partial state.

ALTER TABLE subjects ADD COLUMN credits INT DEFAULT 3;
ALTER TABLE subjects ADD COLUMN batch_year VARCHAR(50);
ALTER TABLE subjects ADD COLUMN semester INT;

ALTER TABLE faculties ADD COLUMN max_hours_week INT DEFAULT 20;

CREATE TABLE IF NOT EXISTS faculty_constraints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    preferred_slots TEXT,
    unavailable_days TEXT,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT,
    year INT,
    semester INT,
    section_name VARCHAR(10),
    student_strength INT DEFAULT 60,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

ALTER TABLE classrooms ADD COLUMN equipment TEXT;

ALTER TABLE timetables ADD COLUMN status ENUM('Draft', 'Generated', 'Approved', 'Locked') DEFAULT 'Draft';
ALTER TABLE timetables ADD COLUMN is_locked TINYINT(1) DEFAULT 0;
ALTER TABLE timetables ADD COLUMN generated_by INT;

CREATE TABLE IF NOT EXISTS timetable_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timetable_id INT,
    user_id INT,
    action_type VARCHAR(50),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS timetable_conflicts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timetable_id INT,
    conflict_type VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
