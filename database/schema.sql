-- AR Lucky Elephant Hunt - Database Schema
-- Run: mysql -u root < database/schema.sql

CREATE DATABASE IF NOT EXISTS ar_elephant_hunt
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ar_elephant_hunt;

CREATE TABLE IF NOT EXISTS players (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  branch VARCHAR(80) NOT NULL,
  nationality VARCHAR(80) NOT NULL,
  items_collected TINYINT UNSIGNED NOT NULL DEFAULT 3,
  reward_code VARCHAR(20) NOT NULL UNIQUE,
  reward_type VARCHAR(100) NOT NULL,
  reward_label_en VARCHAR(200) NOT NULL,
  reward_label_th VARCHAR(200) NOT NULL,
  is_redeemed TINYINT(1) NOT NULL DEFAULT 0,
  redeemed_at DATETIME NULL,
  redeemed_by VARCHAR(80) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_branch (branch),
  INDEX idx_reward_code (reward_code),
  INDEX idx_is_redeemed (is_redeemed),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(120) NOT NULL DEFAULT 'Admin',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin: username=admin, password=admin123
INSERT INTO admin_users (username, password_hash, display_name) VALUES
('admin', '$2y$10$8DlmjckVsEeY2SPXCa.x.eONnDvvQUQ.5aK7kK7zaws/buBahkxpq', 'Store Admin')
ON DUPLICATE KEY UPDATE username = username;

-- Reward pool reference (optional lookup table)
CREATE TABLE IF NOT EXISTS reward_pool (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reward_key VARCHAR(50) NOT NULL UNIQUE,
  label_en VARCHAR(200) NOT NULL,
  label_th VARCHAR(200) NOT NULL,
  weight INT UNSIGNED NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO reward_pool (reward_key, label_en, label_th, weight) VALUES
('discount_10', '10% Discount Coupon', 'คูปองส่วนลด 10%', 30),
('discount_15', '15% Discount Coupon', 'คูปองส่วนลด 15%', 20),
('free_gift', 'Free Souvenir Gift', 'ของที่ระลึกฟรี', 15),
('lucky_bag', 'Lucky Shopping Bag', 'ถุงช้อปปิ้งนำโชค', 20),
('elephant_keychain', 'Elephant Keychain', 'พวงกุญแจช้าง', 15)
ON DUPLICATE KEY UPDATE reward_key = reward_key;
