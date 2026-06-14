DROP DATABASE IF EXISTS `depart_db`;

CREATE DATABASE `depart_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `depart_db`;

CREATE TABLE IF NOT EXISTS `students` (
  `student_id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `password` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `class_name` VARCHAR(80) DEFAULT NULL,
  `department` VARCHAR(120) DEFAULT 'Computer Science',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `staff` (
  `staff_id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `password` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `department` VARCHAR(120) DEFAULT 'Computer Science',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `hod_users` (
  `hod_id` VARCHAR(50) NOT NULL,
  `password` VARCHAR(100) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`hod_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance` (
  `attendance_id` INT NOT NULL AUTO_INCREMENT,
  `student_id` VARCHAR(50) NOT NULL,
  `date` DATE NOT NULL,
  `period` VARCHAR(10) NOT NULL,
  `status` VARCHAR(10) NOT NULL,
  `class_name` VARCHAR(80) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attendance_id`),
  KEY `idx_attendance_student` (`student_id`),
  KEY `idx_attendance_date_period` (`date`, `period`),
  CONSTRAINT `fk_attendance_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `scholarship_applications` (
  `application_id` INT NOT NULL AUTO_INCREMENT,
  `student_id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `scholarship_type` VARCHAR(100) NOT NULL,
  `marks` INT NOT NULL,
  `attendance` INT NOT NULL,
  `backlogs` INT NOT NULL,
  `income` BIGINT NOT NULL,
  `sports_or_not` TINYINT NOT NULL DEFAULT 0,
  `status` VARCHAR(50) NOT NULL,
  `prediction_value` TINYINT NULL,
  `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`application_id`),
  KEY `idx_scholarship_student` (`student_id`),
  CONSTRAINT `fk_scholarship_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `teacher_feedback` (
  `feedback_id` INT NOT NULL AUTO_INCREMENT,
  `student_id` VARCHAR(50) NOT NULL,
  `teacher_name` VARCHAR(150) NOT NULL,
  `feedback_text` TEXT NOT NULL,
  `emotion` VARCHAR(50) DEFAULT 'Not analyzed',
  `video_emotion` VARCHAR(50) DEFAULT 'Not analyzed',
  `video_path` VARCHAR(255) DEFAULT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`feedback_id`),
  KEY `idx_teacher_feedback_student` (`student_id`),
  CONSTRAINT `fk_teacher_feedback_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `department_achievements` (
  `achievement_id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(180) NOT NULL,
  `description` TEXT,
  `image_path` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`achievement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `department_events` (
  `event_id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(180) NOT NULL,
  `event_date` DATE NOT NULL,
  `description` TEXT,
  `image_path` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `students` (`student_id`, `name`, `password`, `email`, `class_name`, `department`) VALUES
  ('student1', 'Student One', 'student123', 'student1@csdepartment.edu.in', '1st Year MCA', 'Computer Science'),
  ('student2', 'Student Two', 'student123', 'student2@csdepartment.edu.in', '1st Year MCA', 'Computer Science'),
  ('student3', 'Student Three', 'student123', 'student3@csdepartment.edu.in', '2nd Year MCA', 'Computer Science')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `email` = VALUES(`email`),
  `class_name` = VALUES(`class_name`),
  `department` = VALUES(`department`);

INSERT INTO `staff` (`staff_id`, `name`, `password`, `email`, `department`) VALUES
  ('staff1', 'Staff One', 'staff123', 'staff1@csdepartment.edu.in', 'Computer Science')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `email` = VALUES(`email`),
  `department` = VALUES(`department`);

INSERT INTO `hod_users` (`hod_id`, `password`, `name`) VALUES
  ('hod', 'hod123', 'Head of Department')
ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`),
  `name` = VALUES(`name`);

INSERT INTO `department_achievements` (`title`, `description`) VALUES
  ('Best Project Award 2025', 'Students received recognition for an innovative academic project.'),
  ('Top Ranking Department', 'The department was recognized for academic performance and student outcomes.'),
  ('Research Grant Received', 'Faculty received support for applied technology research.')
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`);
