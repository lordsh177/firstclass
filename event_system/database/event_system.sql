-- ======================================================
-- DATABASE: Event Attendance Management System
-- ======================================================

-- Create the database
CREATE DATABASE IF NOT EXISTS event_system;

-- Select the database
USE event_system;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- ======================================================
-- 1️⃣ USERS TABLE
-- ======================================================
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  contact VARCHAR(20) NOT NULL,
  gender VARCHAR(20) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  state VARCHAR(100) DEFAULT NULL,
  country VARCHAR(100) DEFAULT NULL,
  password VARCHAR(255) NOT NULL DEFAULT '81dc9bdb52d04dc20036dbd8313ed055', -- md5('1234')
  reg_code VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Admin User
INSERT INTO users (name, email, contact, gender, address, state, country, password, reg_code)
VALUES (
  'Admin User',
  'admin@eventsystem.com',
  '09123456789',
  'Male',
  'Admin Office',
  'Misamis Oriental',
  'Philippines',
  MD5('1234'),
  'REG-ADMIN'
);

-- ======================================================
-- 2️⃣ EVENTS TABLE
-- ======================================================
DROP TABLE IF EXISTS events;

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  date DATE NOT NULL,
  location VARCHAR(255) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Example Event (Optional)
INSERT INTO events (name, date, location, description)
VALUES ('Orientation Day', CURDATE(), 'Campus Hall', 'University orientation event');

-- ======================================================
-- 3️⃣ ATTENDANCE TABLE
-- ======================================================
DROP TABLE IF EXISTS attendance;

CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NOT NULL,
  check_in DATETIME DEFAULT NULL,
  check_out DATETIME DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- ======================================================
-- 4️⃣ (OPTIONAL) AUDIT LOGS TABLE
-- ======================================================
DROP TABLE IF EXISTS audit_logs;

CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user VARCHAR(255),
  action VARCHAR(255),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample Audit Log Entry
INSERT INTO audit_logs (user, action)
VALUES ('Admin User', 'Database initialized');

-- ======================================================
-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ======================================================
-- ✅ SETUP COMPLETE
-- DATABASE NAME: event_system
-- TABLES: users, events, attendance, audit_logs
-- ADMIN ACCOUNT:
--   Email: admin@eventsystem.com
--   Password: 1234
-- ======================================================
