<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS - Hotel Room Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-logo">
                <img src="logo.png" alt="HRMS Logo">
            </div>
            <h1>Welcome to HRMS</h1>
            <p>Experience luxury and comfort with our premium hotel room management system. Find your perfect stay with ease.</p>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="auth-buttons">
                    <a href="auth/login.php" class="btn btn-primary btn-large">Get Started</a>
                </div>
            <?php else: ?>
                <div class="dashboard-link">
                    <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'; ?>" 
                       class="btn btn-primary btn-large">
                        Go to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="why-choose-us">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose Us</h2>
                <p>Everything you need for a perfect stay experience</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3>Free WiFi</h3>
                    <p>High-speed internet connectivity in all rooms and common areas</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <h3>Breakfast Included</h3>
                    <p>Complimentary breakfast buffet with fresh local ingredients</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>Free Parking</h3>
                    <p>Secure parking facility available for all our guests</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>24/7 Security</h3>
                    <p>Round-the-clock security service ensuring your safety</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Flexible Booking</h3>
                    <p>Easy booking process with flexible cancellation policies</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-bed"></i>
                    </div>
                    <h3>Premium Rooms</h3>
                    <p>Comfortable and spacious rooms with modern amenities</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Rooms Preview Section -->
    <section class="why-choose-us" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="section-header">
                <h2>Our Room Types</h2>
                <p>Discover our variety of comfortable accommodations</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-item">
                    <h3>Single Room</h3>
                    <p class="price">From Rs.100/night</p>
                    <p>Perfect for solo travelers with all essential amenities</p>
                </div>
                
                <div class="feature-item">
                    <h3>Double Room</h3>
                    <p class="price">From Rs.150/night</p>
                    <p>Spacious accommodation ideal for couples or friends</p>
                </div>
                
                <div class="feature-item">
                    <h3>Deluxe Room</h3>
                    <p class="price">From Rs.200/night</p>
                    <p>Extra comfort and space with premium amenities</p>
                </div>
                
                <div class="feature-item">
                    <h3>Suite</h3>
                    <p class="price">From Rs.250/night</p>
                    <p>Luxurious suite with separate living area and premium services</p>
                </div>
            </div>


            <?php if (!isset($_SESSION['user_id'])): ?>
            <section class="card" style="margin-top: 40px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white;">
                <div style="text-align: center; padding: 30px;">
                    <h2 style="margin-bottom: 15px; color: white;">Ready to Book Your Stay?</h2>
                    <p style="margin-bottom: 25px; opacity: 0.9;">
                        Create an account or login to book rooms, manage your bookings, and enjoy exclusive features.
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <a href="auth/login.php" class="btn btn-primary" style="background: white; color: var(--primary);">Login</a>
                        <a href="auth/signup.php" class="btn btn-outline" style="border-color: white; color: white;">Sign Up</a>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>