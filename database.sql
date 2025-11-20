-- Create database
CREATE DATABASE IF NOT EXISTS HRMS2;
USE HRMS2;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('guest', 'admin') DEFAULT 'guest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'booked') DEFAULT 'available',
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    room_id INT,
    checkin DATE NOT NULL,
    checkout DATE NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    total_price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE
);

-- Payments table (optional extension)
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'online') DEFAULT 'cash',
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Insert sample admin user
-- Default password: password123 (hashed)
INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample rooms
INSERT INTO rooms (type, price, status, description) VALUES 
('Single', 100.00, 'available', 'Comfortable single room with basic amenities including a queen-sized bed, work desk, and private bathroom. Perfect for solo travelers.'),
('Double', 150.00, 'available', 'Spacious double room perfect for couples or friends, featuring two double beds, sitting area, and modern amenities.'),
('Suite', 250.00, 'available', 'Luxurious suite with premium amenities including separate living area, king-sized bed, jacuzzi, and city views.'),
('Deluxe', 200.00, 'available', 'Deluxe room with extra space and comfort, featuring a king-sized bed, mini-bar, and premium bathroom amenities.');

-- Insert sample bookings
INSERT INTO bookings (user_id, room_id, checkin, checkout, status, total_price) VALUES 
(1, 2, '2024-01-15', '2024-01-18', 'confirmed', 450.00),
(1, 3, '2024-01-22', '2024-01-25', 'pending', 750.00);

-- Create indexes for better performance
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_room_type ON rooms(type);
CREATE INDEX idx_booking_dates ON bookings(checkin, checkout);
CREATE INDEX idx_booking_status ON bookings(status);
CREATE INDEX idx_booking_user ON bookings(user_id);

-- Create views for common queries
CREATE VIEW available_rooms AS
SELECT * FROM rooms WHERE status = 'available';

CREATE VIEW confirmed_bookings AS
SELECT b.*, u.name as user_name, u.email, r.type as room_type, r.price as room_price
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN rooms r ON b.room_id = r.room_id
WHERE b.status = 'confirmed';

CREATE VIEW monthly_revenue AS
SELECT 
    YEAR(created_at) as year,
    MONTH(created_at) as month,
    COUNT(*) as bookings,
    SUM(total_price) as revenue
FROM bookings 
WHERE status = 'confirmed'
GROUP BY YEAR(created_at), MONTH(created_at);