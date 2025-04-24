-- INSTRUCTIONS:
-- 1. Log in to MySQL (or a database management tool like phpMyAdmin).
-- 2. Create a new database by running: CREATE DATABASE bookstore;
-- 3. Select the database: USE bookstore;
-- 4. Run all the SQL statements below to create tables and insert sample data.
-- 5. Update the database connection details in the file `includes/db.php` with your own information (host, username, password, database name).

-- Create the database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS bookstore;
USE bookstore;

-- Create the `users` table (to store user information)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the `products` table (to store product/book information)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the `orders` table (to store order information)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert sample data into the `users` table
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', '$2y$10$5fXhN5a6gZ1zZ2kL7vM5kO0vU5zQ5eW5qX5yZ5kW5vM5kO0vU5zQ', 'admin'), -- Password: "admin123" (hashed)
('user1', 'user1@example.com', '$2y$10$5fXhN5a6gZ1zZ2kL7vM5kO0vU5zQ5eW5qX5yZ5kW5vM5kO0vU5zQ', 'user'); -- Password: "user123" (hashed)

-- Insert sample data into the `products` table
INSERT INTO products (name, image, price) VALUES
('Coco Goose', 'arrival_1.jpg', 25.50),
('Subtlety', 'arrival_2.jpg', 25.50),
('Westpart', 'arrival_3.jpg', 25.50),
('Book 4', 'arrival_4.jpg', 25.50),
('Clever Lands', 'arrival_5.jpg', 25.50),
('Book 6', 'arrival_6.jpg', 25.50),
('Book 7', 'arrival_7.jpg', 25.50),
('Book 8', 'arrival_8.webp', 25.50),
('Book 9', 'arrival_9.jpg', 25.50),
('Book 10', 'arrival_10.jpg', 25.50);

-- Insert sample data into the `orders` table (optional)
INSERT INTO orders (user_id, product_id, quantity, total_price, order_date) VALUES
(2, 1, 2, 51.00, NOW()),
(2, 3, 1, 25.50, NOW());