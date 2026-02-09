-- ==========================================
-- SPARK'26 Database Schema
-- Complete database setup for the project
-- ==========================================

CREATE DATABASE IF NOT EXISTS spark;
USE spark;

-- ==========================================
-- USERS TABLE
-- Stores all user accounts (students, admins, coordinators, student affairs)
-- ==========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(50) DEFAULT NULL,
    year VARCHAR(20) DEFAULT NULL,
    reg_no VARCHAR(12) DEFAULT NULL,
    role ENUM('student', 'admin', 'departmentcoordinator', 'studentaffairs') NOT NULL DEFAULT 'student',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- PROJECTS TABLE
-- Stores all project submissions
-- ==========================================
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT NULL,
    student_id INT NOT NULL,
    department VARCHAR(50) DEFAULT NULL,
    team_members TEXT DEFAULT NULL,
    github_link VARCHAR(255) DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT DEFAULT NULL,
    review_comments TEXT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    score INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- TEAMS TABLE
-- Stores team information
-- ==========================================
CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    project_id INT DEFAULT NULL,
    leader_id INT NOT NULL,
    department VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (leader_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- TEAM MEMBERS TABLE
-- Stores team membership
-- ==========================================
CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- ANNOUNCEMENTS TABLE
-- Stores system announcements
-- ==========================================
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    author_id INT NOT NULL,
    target_role ENUM('all', 'student', 'admin', 'departmentcoordinator', 'studentaffairs') DEFAULT 'all',
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- MESSAGES TABLE
-- Stores internal messages between users
-- ==========================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- SCHEDULE TABLE
-- Stores event schedule and deadlines
-- ==========================================
CREATE TABLE IF NOT EXISTS schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    event_type VARCHAR(50) DEFAULT 'general',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- SETTINGS TABLE
-- Stores system-wide settings
-- ==========================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- DEFAULT DATA INSERTS
-- ==========================================

-- Default Admin User
INSERT INTO users (name, username, email, password, role) VALUES 
('System Admin', 'admin', 'admin@spark.com', 'admin123', 'admin');

-- Default Department Coordinator
INSERT INTO users (name, username, email, password, department, role) VALUES 
('CSE Coordinator', 'coordcse', 'coord.cse@spark.com', 'coord123', 'CSE', 'departmentcoordinator');

-- Default Student Affairs
INSERT INTO users (name, username, email, password, role) VALUES 
('Student Affairs Head', 'affairs', 'affairs@spark.com', 'affairs123', 'studentaffairs');

-- Default Student
INSERT INTO users (name, username, email, password, department, year, reg_no, role) VALUES 
('John Doe', 'johndoe', 'student@spark.com', 'student123', 'CSE', 'III year', '612223104088', 'student');

-- Default Announcements
INSERT INTO announcements (title, message, author_id, target_role, is_featured) VALUES 
('Welcome to SPARK''26!', 'We are excited to announce that SPARK''26 registration is now open! This year''s event promises to be bigger and better than ever. Get ready to showcase your innovative projects and compete with the brightest minds on campus.', 1, 'all', 1),
('Submission Guidelines Updated', 'Please review the updated project submission guidelines. We have added new categories and revised the documentation requirements. Make sure to check the Guidelines page for complete details.', 1, 'all', 0),
('Workshop: Project Presentation Tips', 'Join us for a special workshop on how to effectively present your projects. Learn tips and tricks from previous winners and industry experts.', 3, 'all', 0);

-- Default Schedule
INSERT INTO schedule (title, description, event_date, event_type, created_by) VALUES 
('Registration Opens', 'Start registering your projects and teams', '2026-02-01 09:00:00', 'milestone', 1),
('Project Submission Deadline', 'Last date to submit your project details', '2026-02-15 23:59:00', 'deadline', 1),
('Review Phase', 'Projects will be reviewed by department coordinators', '2026-02-20 09:00:00', 'milestone', 1),
('Final Presentations', 'Present your projects to the judging panel', '2026-02-25 09:00:00', 'event', 1),
('Awards Ceremony', 'Winners announcement and prize distribution', '2026-02-28 14:00:00', 'event', 1);

-- Default Settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('event_name', 'SPARK''26'),
('event_date', '2026-02-15'),
('submission_deadline', '2026-02-15 23:59:00'),
('max_team_size', '4'),
('registration_open', '1');
