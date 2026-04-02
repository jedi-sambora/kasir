-- ============================================
-- Cafe POS System Database Setup
-- ============================================

CREATE DATABASE IF NOT EXISTS cafe_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cafe_pos;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    nama_produk VARCHAR(150) NOT NULL,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    image_url VARCHAR(255) DEFAULT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Aktif, 0=Nonaktif',
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    uang_bayar DECIMAL(12,2) NOT NULL DEFAULT 0,
    kembalian DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Transaction details table
CREATE TABLE IF NOT EXISTS transaction_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    harga_satuan DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================
-- Seed Data
-- ============================================

-- Default admin user (password: admin123)
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Satu', 'kasir');

-- Categories
INSERT INTO categories (nama_kategori) VALUES
('Kopi'),
('Non-Kopi'),
('Makanan Ringan'),
('Makanan Berat'),
('Dessert');

-- Products
INSERT INTO products (category_id, nama_produk, harga, image_url, status) VALUES
-- Kopi
(1, 'Espresso', 18000, NULL, 1),
(1, 'Americano', 22000, NULL, 1),
(1, 'Cappuccino', 28000, NULL, 1),
(1, 'Cafe Latte', 30000, NULL, 1),
(1, 'Mocha Latte', 32000, NULL, 1),
(1, 'Affogato', 35000, NULL, 1),
(1, 'Kopi Susu Gula Aren', 25000, NULL, 1),
(1, 'Vietnam Drip', 26000, NULL, 1),
-- Non-Kopi
(2, 'Matcha Latte', 30000, NULL, 1),
(2, 'Cokelat Panas', 25000, NULL, 1),
(2, 'Teh Tarik', 20000, NULL, 1),
(2, 'Lemon Tea', 18000, NULL, 1),
(2, 'Jus Jeruk', 22000, NULL, 1),
(2, 'Milkshake Vanilla', 28000, NULL, 1),
(2, 'Smoothie Berry', 32000, NULL, 1),
-- Makanan Ringan
(3, 'Kentang Goreng', 25000, NULL, 1),
(3, 'Roti Bakar', 20000, NULL, 1),
(3, 'Pisang Goreng', 18000, NULL, 1),
(3, 'Croissant', 22000, NULL, 1),
(3, 'Donat Gula', 15000, NULL, 1),
-- Makanan Berat
(4, 'Nasi Goreng', 35000, NULL, 1),
(4, 'Mie Goreng', 30000, NULL, 1),
(4, 'Chicken Sandwich', 38000, NULL, 1),
(4, 'Beef Burger', 45000, NULL, 1),
(4, 'Pasta Carbonara', 42000, NULL, 1),
-- Dessert
(5, 'Cheesecake', 30000, NULL, 1),
(5, 'Tiramisu', 35000, NULL, 1),
(5, 'Brownies', 25000, NULL, 1),
(5, 'Pancake', 28000, NULL, 1),
(5, 'Es Krim Scoop', 15000, NULL, 1);
