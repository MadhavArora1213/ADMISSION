CREATE DATABASE IF NOT EXISTS admission;
USE admission;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- The password is 'admin123' hashed using PHP's password_hash() default algorithm (bcrypt)
-- password_hash('admin123', PASSWORD_DEFAULT);
INSERT INTO admins (username, password) 
VALUES ('admin', '$2y$10$sL1O2n1t8pbVzDBNvlSo2.Jf6mQP6vbzIrPeUX3KGOnZTcqL8lXDS')
ON DUPLICATE KEY UPDATE password='$2y$10$sL1O2n1t8pbVzDBNvlSo2.Jf6mQP6vbzIrPeUX3KGOnZTcqL8lXDS';
