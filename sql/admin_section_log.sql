CREATE TABLE admin_section_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    section VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    action_time DATETIME NOT NULL
);
