CREATE DATABASE IF NOT EXISTS sfit_attendance
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sfit_attendance;

CREATE TABLE IF NOT EXISTS teachers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS classes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  class_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(30) NOT NULL UNIQUE,
  full_name VARCHAR(100) NOT NULL,
  class_id INT UNSIGNED NOT NULL,
  CONSTRAINT fk_students_class
    FOREIGN KEY (class_id) REFERENCES classes(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  class_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  teacher_id INT UNSIGNED NOT NULL,
  attendance_date DATE NOT NULL,
  status ENUM('Present', 'Absent', 'Late') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attendance_day (student_id, subject_id, attendance_date),
  CONSTRAINT fk_att_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Default teacher login: teacher / teacher123  (also: admin / teacher123)
INSERT INTO teachers (username, password_hash, full_name) VALUES
('teacher', '$2y$10$Kqc5zg2ggujdy/vWQGyoFez7EeiodEx6CyUqDzOrKMaI2fBr0Y9Hm', 'Maria Santos'),
('admin', '$2y$10$Kqc5zg2ggujdy/vWQGyoFez7EeiodEx6CyUqDzOrKMaI2fBr0Y9Hm', 'Juan Dela Cruz')
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), full_name = VALUES(full_name);

INSERT INTO classes (class_name) VALUES
('Grade 7 - A'),
('Grade 7 - B'),
('Grade 8 - A'),
('Grade 9 - A'),
('Grade 10 - A')
ON DUPLICATE KEY UPDATE class_name = VALUES(class_name);

INSERT INTO subjects (subject_name) VALUES
('Mathematics'),
('English'),
('Science'),
('Filipino'),
('History')
ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name);

INSERT INTO students (student_id, full_name, class_id) VALUES
('STU-001', 'Ana Reyes', 1),
('STU-002', 'Carlo Mendoza', 1),
('STU-003', 'Diana Lopez', 1),
('STU-004', 'Ethan Cruz', 1),
('STU-005', 'Faye Garcia', 1),
('STU-006', 'Gabriel Torres', 2),
('STU-007', 'Hannah Villanueva', 2),
('STU-008', 'Ian Ramos', 2),
('STU-009', 'Julia Navarro', 2),
('STU-010', 'Kevin Bautista', 2),
('STU-011', 'Lara Domingo', 3),
('STU-012', 'Miguel Santos', 3),
('STU-013', 'Nina Aquino', 3),
('STU-014', 'Oscar Lim', 4),
('STU-015', 'Paula Fernandez', 4),
('STU-016', 'Quinn Morales', 5),
('STU-017', 'Rina Castillo', 5),
('STU-018', 'Samuel Perez', 5)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), class_id = VALUES(class_id);
