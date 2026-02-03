CREATE DATABASE IF NOT EXISTS crypto_exchange CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crypto_exchange;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(100) NULL,
    verification_sent_at DATETIME NULL,
    kyc_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    status ENUM('active', 'banned') DEFAULT 'active',
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS kyc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nin VARCHAR(50) NOT NULL,
    bvn VARCHAR(50) NOT NULL,
    selfie_path VARCHAR(255) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    submitted_at DATETIME NOT NULL,
    approved_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    currency VARCHAR(10) NOT NULL,
    balance DECIMAL(20,8) NOT NULL DEFAULT 0,
    UNIQUE KEY unique_wallet (user_id, currency),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('buy', 'sell', 'swap', 'deposit', 'withdraw') NOT NULL,
    coin VARCHAR(10) NOT NULL,
    amount DECIMAL(20,8) NOT NULL,
    rate_used DECIMAL(20,8) NOT NULL,
    reference VARCHAR(255) NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coin VARCHAR(10) NOT NULL UNIQUE,
    market_rate DECIMAL(20,8) NOT NULL,
    buy_rate DECIMAL(20,8) NOT NULL,
    sell_rate DECIMAL(20,8) NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    read_status TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS platform_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usd_to_ngn DECIMAL(20,8) NOT NULL,
    created_at DATETIME NOT NULL
);

INSERT INTO rates (coin, market_rate, buy_rate, sell_rate, enabled, updated_at) VALUES
('BTC', 64000, 97000000, 95500000, 1, NOW()),
('ETH', 3200, 4900000, 4750000, 1, NOW()),
('USDT', 1, 1550, 1500, 1, NOW()),
('BNB', 580, 890000, 860000, 1, NOW())
ON DUPLICATE KEY UPDATE market_rate = VALUES(market_rate), buy_rate = VALUES(buy_rate), sell_rate = VALUES(sell_rate), enabled = VALUES(enabled), updated_at = NOW();

INSERT INTO platform_rates (usd_to_ngn, created_at) VALUES (1500, NOW());
