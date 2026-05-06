-- IM Cash Tracker: Additional tables only
-- ospos_employees and ospos_people tables already exist in OSPOS database

USE ospos;

CREATE TABLE IF NOT EXISTS `ospos_cash_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `person_id` INT(10) NOT NULL,
    `cashier` VARCHAR(20) NOT NULL,
    `record_date` DATE NOT NULL,
    `record_time` TIME NOT NULL,
    `rp100k` INT DEFAULT 0,
    `rp50k` INT DEFAULT 0,
    `rp20k` INT DEFAULT 0,
    `rp10k` INT DEFAULT 0,
    `rp5k` INT DEFAULT 0,
    `rp2k` INT DEFAULT 0,
    `rp1k` INT DEFAULT 0,
    `coin_total` INT DEFAULT 0,
    `total_kutipan` INT DEFAULT 0,
    `total_di_kasir` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`person_id`) REFERENCES `ospos_employees`(`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ospos_employees` ADD COLUMN IF NOT EXISTS `remember_token` VARCHAR(128) DEFAULT NULL;
ALTER TABLE `ospos_employees` ADD COLUMN IF NOT EXISTS `remember_expires` DATETIME DEFAULT NULL;