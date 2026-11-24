CREATE DATABASE IF NOT EXISTS campus_marketplace
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE campus_marketplace;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS ai_chat_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS stalls;
DROP TABLE IF EXISTS event_applications;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS shopping_carts;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS vendor_profiles;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'vendor', 'admin') NOT NULL DEFAULT 'student',
    phone VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Vendor Profiles
CREATE TABLE vendor_profiles (
    vendor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    business_name VARCHAR(150) NOT NULL,
    business_description TEXT NULL,
    contact_information VARCHAR(150) NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. Products
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    category VARCHAR(80) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    availability_status ENUM('available', 'out_of_stock', 'hidden') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendor_profiles(vendor_id) ON DELETE CASCADE
);

-- 4. Shopping Carts
CREATE TABLE shopping_carts (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. Cart Items
CREATE TABLE cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES shopping_carts(cart_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- 6. Orders
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    order_status ENUM('pending', 'confirmed', 'ready_for_pickup', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 7. Order Items
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    vendor_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendor_profiles(vendor_id) ON DELETE CASCADE
);

-- 8. Events (Person 4 Module)
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,
    event_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    venue VARCHAR(150) NOT NULL,
    event_date DATETIME NOT NULL,
    max_stalls INT NOT NULL DEFAULT 20,
    event_status ENUM('draft', 'published', 'completed', 'cancelled') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 9. Event Applications (Person 4 Module)
CREATE TABLE event_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    vendor_id INT NOT NULL,
    product_category VARCHAR(100) NOT NULL,
    notes TEXT NULL,
    application_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_event_vendor (event_id, vendor_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendor_profiles(vendor_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- 10. Stalls (Person 4 Module)
CREATE TABLE stalls (
    stall_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    vendor_id INT NOT NULL,
    stall_number VARCHAR(20) NOT NULL,
    stall_location VARCHAR(120) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_event_stall_num (event_id, stall_number),
    UNIQUE KEY uq_event_stall_vendor (event_id, vendor_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendor_profiles(vendor_id) ON DELETE CASCADE
);

-- 11. Notifications (Person 4 Module)
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('order', 'event', 'stall', 'system') NOT NULL DEFAULT 'system',
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 12. AI Chat Logs (Person 4 Module)
CREATE TABLE ai_chat_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_role VARCHAR(20) NOT NULL DEFAULT 'student',
    user_message TEXT NOT NULL,
    ai_response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);



INSERT INTO users (user_id, full_name, email, password_hash, role, phone) VALUES
(1, 'Ama Mensah', 'ama.mensah@ashesi.edu.gh', '$2y$10$demoHashStudent1', 'student', '+233240000001'),
(2, 'Kwame Boateng', 'kwame.boateng@ashesi.edu.gh', '$2y$10$demoHashVendor1', 'vendor', '+233240000002'),
(3, 'Efua Asante', 'efua.asante@ashesi.edu.gh', '$2y$10$demoHashVendor2', 'vendor', '+233240000003'),
(4, 'Dr. Kofi Osei', 'kofi.osei@ashesi.edu.gh', '$2y$10$demoHashAdmin1', 'admin', '+233240000004');

INSERT INTO vendor_profiles (vendor_id, user_id, business_name, business_description, contact_information, approval_status) VALUES
(1, 2, 'Berekuso Bites & Smoothies', 'Fresh fruit smoothies, meat pies, and spicy kelewele boxes made on campus.', 'Instagram: @berekusobites | +233240000002', 'approved'),
(2, 3, 'Ashesi Craft & Tech Hub', 'Laptop sleeves, beaded jewelry, custom stickers, and phone stands.', 'WhatsApp: +233240000003', 'approved');

INSERT INTO products (product_id, vendor_id, product_name, category, description, price, stock_quantity, availability_status) VALUES
(1, 1, 'Mango Pineapple Smoothie', 'Food & Drinks', 'Chilled 500ml fresh tropical blend with zero added sugar.', 25.00, 30, 'available'),
(2, 1, 'Spicy Kelewele & Peanut Box', 'Food & Drinks', 'Crispy spiced plantains served with roasted groundnuts.', 30.00, 25, 'available'),
(3, 1, 'Flaky Beef Meat Pie', 'Food & Drinks', 'Warm baked pastry stuffed with seasoned minced beef.', 18.00, 40, 'available'),
(4, 2, 'Kente-Trim Laptop Sleeve (14-inch)', 'Accessories', 'Padded protective laptop sleeve with woven Ghanaian Kente trim.', 120.00, 12, 'available'),
(5, 2, 'Adjustable Aluminum Phone Stand', 'Tech Gadgets', 'Foldable desk stand ideal for Zoom lectures and dorm desks.', 55.00, 18, 'available'),
(6, 2, 'Ashesi Engineering Vinyl Sticker Pack', 'Stationery & Art', 'Set of 6 waterproof laptop stickers.', 20.00, 50, 'available');

INSERT INTO events (event_id, created_by, event_name, description, venue, event_date, max_stalls, event_status) VALUES
(1, 4, 'Campus Cultural & Entrepreneurship Week', 'Annual student marketplace celebrating local food, handmade crafts, and student-led startups.', 'The Courtyard, Ashesi University', '2026-12-05 10:00:00', 25, 'published'),
(2, 4, 'Freshers Welcome Night Market', 'Evening pop-up market with snacks, dorm essentials, and tech accessories for new students.', 'Archer Cornfield Courtyard', '2026-12-12 16:00:00', 15, 'published');

INSERT INTO event_applications (application_id, event_id, vendor_id, product_category, notes, application_status, reviewed_by, reviewed_at) VALUES
(1, 1, 1, 'Food & Drinks', 'Need access to one power outlet for our blender.', 'approved', 4, NOW()),
(2, 1, 2, 'Crafts & Tech Accessories', 'Bringing display rack for laptop sleeves and stands.', 'pending', NULL, NULL),
(3, 2, 1, 'Food & Drinks', 'Serving evening snacks and smoothies.', 'approved', 4, NOW());

INSERT INTO stalls (stall_id, event_id, vendor_id, stall_number, stall_location) VALUES
(1, 1, 1, 'A-01', 'North Courtyard - Near Power Pillar'),
(2, 2, 1, 'B-03', 'West Pavilion Entrance');

INSERT INTO notifications (notification_id, user_id, title, message, type, link, is_read) VALUES
(1, 2, 'Stall A-01 Assigned', 'Your application for Campus Cultural & Entrepreneurship Week was approved. You are assigned to Stall A-01 (North Courtyard - Near Power Pillar).', 'stall', 'vendor/events.php', 0),
(2, 1, 'New Campus Event Published', 'Campus Cultural & Entrepreneurship Week is now live! Browse participating vendors and stalls.', 'event', 'events.php', 0),
(3, 4, 'New Vendor Application', 'Ashesi Craft & Tech Hub applied for a stall at Campus Cultural & Entrepreneurship Week.', 'stall', 'admin/events.php', 0);