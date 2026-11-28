
CREATE DATABASE campus_marketplace;

USE campus_marketplace;

-- 1. USERS
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('buyer', 'vendor', 'admin') NOT NULL,
    account_status ENUM('active', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. VENDOR PROFILES
CREATE TABLE vendor_profiles (
    vendor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    business_name VARCHAR(150) NOT NULL,
    description TEXT,
    phone VARCHAR(30),
    business_address VARCHAR(255),
    approval_status ENUM('pending', 'approved', 'rejected')
        DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

-- 3. PRODUCT CATEGORIES
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO categories (category_name) VALUES
('Beauty'),
('Food'),
('Basic Needs'),
('Beadwork and Crafts'),
('Fashion'),
('Electronics'),
('Services');

-- 4. PRODUCTS
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    category_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_path VARCHAR(255),
    product_status ENUM('active', 'inactive')
        DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (vendor_id)
        REFERENCES vendor_profiles(vendor_id),

    FOREIGN KEY (category_id)
        REFERENCES categories(category_id),

    CHECK (price >= 0),
    CHECK (stock_quantity >= 0)
);
