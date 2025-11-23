<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Room Management System</title>
    <link rel="stylesheet" href="/version2/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .featured-rooms {
            padding: 80px 0;
            background-color: var(--white-dove);
        }
        
        .room-card-home {
            text-align: center;
            padding: 30px 20px;
            transition: var(--transition);
            height: 100%;
        }
        
        .room-card-home:hover {
            transform: translateY(-5px);
        }
        
        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
    </style>
</head>
<body>
  

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-logo">
                <div class="logo-circle" style="width: 120px; height: 120px; background: rgba(255, 255, 255, 0.2); margin: 0 auto 30px; color: white;">
                    <i class="fas fa-hotel" style="font-size: 3rem;"></i>
                </div>
            </div>
            <h1 style="font-size: 3rem; margin-bottom: 20px; font-weight: 700;">Welcome to Our Lobby</h1>
            <p style="font-size: 1.2rem; max-width: 600px; margin: 0 auto 30px; opacity: 0.9;">
                Experience luxury and comfort with our premium hotel room management system. 
                Find your perfect stay with ease.
            </p>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="auth-buttons" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="/version2/auth/login.php" class="btn btn-primary" style="padding: 12px 30px; font-size: 1.1rem; background: white; color: var(--primary);">Get Started</a>
                    <a href="/version2/guest/rooms.php" class="btn btn-outline" style="border-color: white; color: white; padding: 12px 30px; font-size: 1.1rem;">View Rooms</a>
                </div>
            <?php else: ?>
                <div class="dashboard-link">
                    <a href="<?php echo $_SESSION['role'] === 'admin' ? '/version2/admin/admin_dashboard.php' : 
                                         ($_SESSION['role'] === 'staff' ? '/version2/hotel staff/staff_dashboard.php' : '/version2/guest/guest_dashboard.php'); ?>" 
                       class="btn btn-primary" style="padding: 12px 30px; font-size: 1.1rem; background: white; color: var(--primary);">
                        Go to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Featured Rooms Section -->
    

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

    <!-- Call to Action Section -->
    <?php if (!isset($_SESSION['user_id'])): ?>
    <section class="cta-section">
        <div class="container">
            <h2 style="margin-bottom: 15px; color: white; font-size: 1.8rem;">Ready to Book Your Stay?</h2>
            <p style="margin-bottom: 25px; opacity: 0.9; font-size: 1.1rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                Create an account or login to book rooms, manage your bookings, and enjoy exclusive features.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="/version2/auth/login.php" class="btn btn-primary" style="background: white; color: var(--primary); padding: 12px 25px;">Login</a>
                <a href="/version2/auth/signup.php" class="btn btn-outline" style="border-color: white; color: white; padding: 12px 25px;">Sign Up</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer style="background-color: var(--primary-dark); color: white; padding: 50px 0 20px;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 30px;">
                <div>
                    <h4 style="color: white; margin-bottom: 15px;">Contact Info</h4>
                    <p style="opacity: 0.8;"><i class="fas fa-map-marker-alt"></i> Chowk tira</p>
                    <p style="opacity: 0.8;"><i class="fas fa-phone"></i> +977 9803040024</p>
                    <p style="opacity: 0.8;"><i class="fas fa-envelope"></i> hotel@hrms.com</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>