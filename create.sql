CREATE DATABASE campus_lost_found;

USE campus_lost_found;

CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    location VARCHAR(100) NOT NULL,
    item_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add a few sample items
INSERT INTO items
(name, category, location, item_date, status, image_url)
VALUES
('Black Wallet', 'Accessories', 'Library', '2026-10-18',
 'Lost', ''),
('Blue Water Bottle', 'Accessories', 'Cafeteria', '2026-10-19',
 'Found', ''),
('Scientific Calculator', 'Electronics', 'Block A', '2026-10-20',
 'Found', '');