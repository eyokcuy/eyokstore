-- GameTopUp Pro - Database Schema & Seed Data
-- MySQL 8.x

-- Drop tables if exist (for clean setup)
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS games;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- ============================================
-- 1. users table
-- ============================================
CREATE TABLE users (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    username      VARCHAR(50) UNIQUE NOT NULL,
    password      VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    avatar        VARCHAR(255) DEFAULT NULL,
    role          ENUM('admin','operator') DEFAULT 'operator',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. categories table
-- ============================================
CREATE TABLE categories (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    description   TEXT DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 3. games table
-- ============================================
CREATE TABLE games (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    category_id   INT NOT NULL,
    name          VARCHAR(100) NOT NULL,
    thumbnail     VARCHAR(255) DEFAULT NULL,
    publisher     VARCHAR(100) DEFAULT NULL,
    status        ENUM('active','inactive') DEFAULT 'active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- ============================================
-- 4. transactions table
-- ============================================
CREATE TABLE transactions (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    invoice_code   VARCHAR(50) UNIQUE NOT NULL,
    user_id        INT NOT NULL,
    game_id        INT NOT NULL,
    customer_name  VARCHAR(100) NOT NULL,
    game_uid       VARCHAR(50) NOT NULL,
    item_name      VARCHAR(100) NOT NULL,
    quantity       INT NOT NULL DEFAULT 1,
    price          DECIMAL(15,2) NOT NULL,
    total          DECIMAL(15,2) NOT NULL,
    status         ENUM('pending','success','failed') DEFAULT 'pending',
    notes          TEXT DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE RESTRICT
);

-- ============================================
-- SEED DATA
-- ============================================

-- Default admin user (password: admin123, bcrypt hashed)
INSERT INTO users (username, password, full_name, avatar, role, created_at) VALUES
('admin', '$2y$12$Vqy.eSqhpu.lkvYW/DCv/ez5JGXPvhsU.8Pl6JjRU5xGTmWyLYuGG', 'Administrator', NULL, 'admin', NOW());

-- Sample operator user (password: operator123, bcrypt hashed)
INSERT INTO users (username, password, full_name, avatar, role, created_at) VALUES
('operator', '$2y$12$Vqy.eSqhpu.lkvYW/DCv/ez5JGXPvhsU.8Pl6JjRU5xGTmWyLYuGG', 'Operator User', NULL, 'operator', NOW());

-- Sample categories
INSERT INTO categories (name, description, created_at) VALUES
('MOBA', 'Multiplayer Online Battle Arena games', NOW()),
('Battle Royale', 'Last-man-standing survival games', NOW()),
('RPG', 'Role-Playing Games', NOW());

-- Sample games
INSERT INTO games (category_id, name, thumbnail, publisher, status, created_at) VALUES
(1, 'Mobile Legends: Bang Bang', NULL, 'Moonton', 'active', NOW()),
(1, 'League of Legends: Wild Rift', NULL, 'Riot Games', 'active', NOW()),
(2, 'PUBG Mobile', NULL, 'Tencent Games', 'active', NOW()),
(2, 'Free Fire', NULL, 'Garena', 'active', NOW()),
(3, 'Genshin Impact', NULL, 'miHoYo', 'active', NOW());

-- Sample transactions
INSERT INTO transactions (invoice_code, user_id, game_id, customer_name, game_uid, item_name, quantity, price, total, status, notes, created_at) VALUES
('INV-20250101-A1B2C', 1, 1, 'John Doe', '123456789', 'Weekly Diamond Pass', 2, 50000.00, 100000.00, 'success', 'Paid via bank transfer', DATE_SUB(NOW(), INTERVAL 6 DAY)),
('INV-20250101-D3E4F', 1, 2, 'Jane Smith', '987654321', 'Crystal Pack', 1, 150000.00, 150000.00, 'pending', 'Waiting for payment', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('INV-20250102-G5H6I', 2, 3, 'Bob Johnson', '555666777', 'UC Pack 1000', 3, 200000.00, 600000.00, 'success', 'Repeat customer', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('INV-20250102-J7K8L', 1, 4, 'Alice Brown', '111222333', 'Diamond 500', 1, 75000.00, 75000.00, 'failed', 'Payment declined', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('INV-20250103-M9N0O', 2, 1, 'Charlie Wilson', '444555666', 'Starlight Member', 1, 120000.00, 120000.00, 'success', NULL, DATE_SUB(NOW(), INTERVAL 4 DAY)),
('INV-20250103-P1Q2R', 1, 5, 'Diana Prince', '777888999', 'Genesis Crystal 300', 2, 80000.00, 160000.00, 'pending', 'Customer will pay tomorrow', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('INV-20250104-S3T4U', 2, 2, 'Evan Wright', '333444555', 'Wild Core 100', 5, 25000.00, 125000.00, 'success', 'Bulk order', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('INV-20250104-V5W6X', 1, 3, 'Fiona Green', '666777888', 'Royal Pass', 1, 180000.00, 180000.00, 'success', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('INV-20250105-Y7Z8A', 2, 4, 'George Hall', '222333444', 'Elite Pass', 2, 90000.00, 180000.00, 'failed', 'Wrong game UID', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('INV-20250105-B9C0D', 1, 5, 'Hannah Lee', '999000111', 'Blessing of the Welkin Moon', 3, 65000.00, 195000.00, 'success', 'Monthly subscription', NOW());
