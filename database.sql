-- Create database
CREATE DATABASE IF NOT EXISTS HRMS_9;
USE HRMS_9;

-- Users table with status field for staff approval
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    role ENUM('guest', 'staff', 'admin') DEFAULT 'guest',
    status ENUM('active', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'occupied', 'unavailable') DEFAULT 'available',
    description TEXT,
    amenities TEXT,
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
    guests INT DEFAULT 1,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
    total_price DECIMAL(10,2),
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Insert sample admin user
-- Default password: admin123 (hashed)
INSERT INTO users (name, username, email, phone, password, role, status) VALUES 
('Admin User', 'admin', 'admin@hotel.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert sample rooms
INSERT INTO rooms (type, price, status, description, amenities) VALUES 
('Single', 100.00, 'available', 'Comfortable single room with basic amenities', 'WiFi, TV, AC, Private Bathroom'),
('Double', 150.00, 'available', 'Spacious double room perfect for couples', 'WiFi, TV, AC, Private Bathroom, Mini Bar'),
('Suite', 250.00, 'available', 'Luxurious suite with premium amenities', 'WiFi, TV, AC, Private Bathroom, Mini Bar, Living Area'),
('Deluxe', 200.00, 'available', 'Deluxe room with extra space and comfort', 'WiFi, TV, AC, Private Bathroom, Mini Bar, Balcony');

-- Create indexes for better performance
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_user_username ON users(username);
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
WHERE status IN ('confirmed', 'checked_in', 'checked_out')
GROUP BY YEAR(created_at), MONTH(created_at);