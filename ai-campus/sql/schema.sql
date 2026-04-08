-- AI Campus Management System Database Schema

CREATE DATABASE IF NOT EXISTS ai_campus;
USE ai_campus;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin','faculty','student','staff') DEFAULT 'student',
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Devices table
CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    total_units INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Device borrowings table
CREATE TABLE IF NOT EXISTS borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrower_name VARCHAR(100) NOT NULL,
    borrower_email VARCHAR(100) NULL,
    device_id INT NOT NULL,
    device_label VARCHAR(100) NOT NULL,
    borrow_date DATETIME NOT NULL,
    return_date DATETIME NULL,
    status ENUM('active','returned','overdue') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id)
);

-- Lost and found table
CREATE TABLE IF NOT EXISTS lost_found (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    type ENUM('lost','found') NOT NULL,
    location VARCHAR(150),
    posted_by VARCHAR(100),
    image VARCHAR(255),
    status ENUM('active','claimed') DEFAULT 'active',
    posted_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Capstone projects table
CREATE TABLE IF NOT EXISTS capstone_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    year YEAR NOT NULL,
    github_url VARCHAR(255),
    advisor VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Capstone authors
CREATE TABLE IF NOT EXISTS capstone_authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (project_id) REFERENCES capstone_projects(id) ON DELETE CASCADE
);

-- Capstone technologies
CREATE TABLE IF NOT EXISTS capstone_technologies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    technology VARCHAR(50) NOT NULL,
    FOREIGN KEY (project_id) REFERENCES capstone_projects(id) ON DELETE CASCADE
);

-- Community service table
CREATE TABLE IF NOT EXISTS community_service (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    violation VARCHAR(200) NOT NULL,
    required_hours INT NOT NULL,
    completed_hours INT DEFAULT 0,
    supervisor VARCHAR(100) NOT NULL,
    status ENUM('pending','in_progress','completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description TEXT NOT NULL,
    icon VARCHAR(50) DEFAULT 'circle',
    color VARCHAR(20) DEFAULT 'blue',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data
INSERT INTO users (name, email, role) VALUES
('Dr. Maria Santos', 'maria.santos@university.edu', 'admin'),
('Prof. John Martinez', 'john.martinez@university.edu', 'faculty'),
('Dr. Lisa Wong', 'lisa.wong@university.edu', 'faculty');

INSERT INTO devices (name, category, total_units) VALUES
('Projector', 'AV Equipment', 5),
('Laptop', 'Computing', 8),
('HDMI Cable', 'Cables', 10),
('Microphone Set', 'AV Equipment', 4),
('Extension Cord', 'Cables', 6),
('USB Hub', 'Accessories', 5),
('Projector Screen', 'AV Equipment', 3),
('Whiteboard Marker Set', 'Supplies', 12);

INSERT INTO borrowings (borrower_name, device_id, device_label, borrow_date, return_date, status) VALUES
('Prof. John Martinez', 1, 'Projector #3', '2024-01-15 09:30:00', NULL, 'active'),
('Dr. Lisa Wong', 3, 'HDMI Cable #2', '2024-01-14 14:20:00', '2024-01-15 10:15:00', 'returned'),
('Prof. Sarah Chen', 2, 'Laptop #5', '2024-01-13 11:00:00', NULL, 'overdue'),
('Dr. Michael Brown', 4, 'Microphone Set #1', '2024-01-15 08:45:00', NULL, 'active'),
('Prof. Emily Davis', 5, 'Extension Cord #4', '2024-01-12 13:30:00', '2024-01-13 09:00:00', 'returned'),
('Dr. Robert Taylor', 8, 'Whiteboard Marker Set', '2024-01-15 10:00:00', NULL, 'active'),
('Prof. Anna Lee', 6, 'USB Hub #2', '2024-01-11 15:20:00', NULL, 'overdue'),
('Dr. James Wilson', 7, 'Projector Screen', '2024-01-14 16:00:00', '2024-01-15 08:30:00', 'returned');

INSERT INTO lost_found (title, description, type, location, posted_by, status, posted_date) VALUES
('Blue Notebook', 'College-ruled notebook with blue cover, contains lecture notes', 'found', 'Room 301', 'Sarah Chen', 'active', '2024-01-15'),
('Black USB Flash Drive', '32GB SanDisk USB drive, has important project files', 'lost', 'Computer Lab B', 'Mark Johnson', 'active', '2024-01-14'),
('Red Water Bottle', 'Stainless steel water bottle with university logo', 'found', 'Cafeteria', 'Admin', 'claimed', '2024-01-13'),
('Eyeglasses with Black Frame', 'Prescription glasses in black rectangular frame', 'found', 'Library 2nd Floor', 'Lisa Wong', 'active', '2024-01-15'),
('Gray Backpack', 'Medium-sized gray backpack with laptop compartment', 'lost', 'Hallway Building A', 'Robert Taylor', 'active', '2024-01-12'),
('Calculator TI-84', 'Graphing calculator with protective case', 'found', 'Math Department', 'Emily Davis', 'active', '2024-01-14');

INSERT INTO capstone_projects (title, description, year, github_url) VALUES
('Smart Parking System with IoT Integration', 'An intelligent parking management system using IoT sensors and real-time data analytics to optimize parking space utilization in urban areas.', 2024, 'https://github.com'),
('AI-Powered Student Performance Predictor', 'Machine learning application that predicts student academic performance based on historical data and behavioral patterns.', 2024, 'https://github.com'),
('Campus Food Delivery Mobile App', 'Mobile application connecting students with campus food vendors, featuring real-time order tracking and payment integration.', 2023, 'https://github.com'),
('Virtual Laboratory Simulation Platform', 'Web-based platform providing virtual laboratory experiments for science students with interactive 3D simulations.', 2023, 'https://github.com'),
('Blockchain-Based Certificate Verification', 'Decentralized system for issuing and verifying academic certificates using blockchain technology.', 2023, 'https://github.com'),
('Automated Attendance System with Face Recognition', 'Computer vision-based attendance tracking system using facial recognition and deep learning algorithms.', 2022, 'https://github.com');

INSERT INTO capstone_authors (project_id, author_name) VALUES
(1,'John Martinez'),(1,'Sarah Chen'),(1,'Michael Brown'),
(2,'Emily Davis'),(2,'Robert Taylor'),
(3,'Lisa Wong'),(3,'James Wilson'),(3,'Anne Lee'),
(4,'David Kim'),(4,'Sophie Martinez'),
(5,'Chris Park'),(5,'Nina Reyes'),
(6,'Alex Turner'),(6,'Maria Cruz');

INSERT INTO capstone_technologies (project_id, technology) VALUES
(1,'IoT'),(1,'React'),(1,'Node.js'),(1,'MongoDB'),
(2,'Python'),(2,'TensorFlow'),(2,'Flask'),(2,'Machine Learning'),
(3,'React Native'),(3,'Firebase'),(3,'Stripe'),(3,'Mobile'),
(4,'Three.js'),(4,'WebGL'),(4,'React'),(4,'Physics Engine'),
(5,'Blockchain'),(5,'Solidity'),(5,'Web3.js'),
(6,'Python'),(6,'OpenCV'),(6,'TensorFlow'),(6,'Deep Learning');

INSERT INTO community_service (student_name, student_id, violation, required_hours, completed_hours, supervisor, status) VALUES
('Mark Johnson', '2021-00123', 'Excessive absences (5 days)', 20, 12, 'Prof. Sarah Chen', 'in_progress'),
('Lisa Anderson', '2021-00498', 'Late submission of requirements', 10, 10, 'Dr. Michael Brown', 'completed'),
('David Kim', '2022-00789', 'Classroom disruption', 15, 0, 'Prof. Emily Davis', 'pending'),
('Rachel Green', '2021-00234', 'Unauthorized absence from exam', 25, 18, 'Dr. Robert Taylor', 'in_progress'),
('Thomas White', '2022-00567', 'Violation of laboratory rules', 12, 12, 'Prof. Anna Lee', 'completed');

INSERT INTO activity_log (description, icon, color) VALUES
('Prof. John Martinez borrowed Projector #3', 'device', 'blue'),
('Sarah Chen posted found item: Blue Notebook', 'search', 'orange'),
('Admin added new capstone project: Smart Parking System', 'book', 'purple'),
('Mark Johnson completed 5 community service hours', 'check', 'green'),
('Dr. Lisa Wong returned HDMI Cable #2', 'device', 'teal');

-- DTR (Daily Time Record) table
CREATE TABLE IF NOT EXISTS dtr_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    student_id   VARCHAR(20)  NOT NULL,
    service_id   INT NULL,
    log_date     DATE NOT NULL,
    time_in      TIME NOT NULL,
    time_out     TIME NULL,
    hours_rendered DECIMAL(4,2) DEFAULT 0,
    late_minutes INT DEFAULT 0,
    remarks      VARCHAR(200) DEFAULT '',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES community_service(id) ON DELETE SET NULL
);

-- QR scan log (raw scans before pairing into DTR records)
CREATE TABLE IF NOT EXISTS qr_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id   VARCHAR(20) NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    service_id   INT NULL,
    scan_type    ENUM('in','out') NOT NULL,
    scanned_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES community_service(id) ON DELETE SET NULL
);
