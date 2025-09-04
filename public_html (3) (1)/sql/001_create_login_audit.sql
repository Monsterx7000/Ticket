-- Login Audit table
CREATE TABLE IF NOT EXISTS login_audit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  email VARCHAR(190) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  status ENUM('success','failure') NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (email),
  INDEX (status),
  INDEX (created_at)
);
