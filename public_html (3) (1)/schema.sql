-- Create database (optional, might need to be created in hPanel first)
-- CREATE DATABASE helpdesk_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE helpdesk_db;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','agent','admin') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  assigned_to INT DEFAULT NULL,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  category VARCHAR(100) DEFAULT 'General',
  priority VARCHAR(20) DEFAULT 'Normal',
  status ENUM('open','pending','closed') NOT NULL DEFAULT 'open',
  attachment VARCHAR(120) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  INDEX (status), INDEX (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ticket_replies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  attachment VARCHAR(120) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create default admin (change email/password after first login)
INSERT INTO users (name,email,password,role,created_at) VALUES
('Admin','admin@example.com','$2y$10$7e9mFPOq1mHkBf3w8I1ZsOIg8o8M8LQp9o0bT0p9Y0w9wV5VQy4x6','admin',NOW());
-- The hashed password above is: Admin@123
