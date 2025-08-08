CREATE TABLE IF NOT EXISTS `calendar_schedule_note_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `note_id` VARCHAR(50) NOT NULL,
    `note_date` DATE NOT NULL,
    `note_text` TEXT NOT NULL,
    `user` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;