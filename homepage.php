<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Room Management System</title>
    <meta name="description"
        content="Experience luxury and comfort with our premium hotel room management system. Book your perfect stay today.">
    <link rel="stylesheet" href="/version2/style.css">
    <link rel="stylesheet" href="/version2/assets/fontawesome/css/all.min.css">
    <link href="/version2/assets/fonts/google-fonts-local.css" rel="stylesheet">
</head>

<body class="hp-body">

    <!-- Navbar -->
    <nav class="hp-navbar" id="hp-navbar">
        <div class="container hp-navbar-inner">
            <a href="/version2/homepage.php" class="hp-logo">
                <div class="hp-logo-icon"><i class="fas fa-hotel"></i></div>
                <span class="hp-logo-text">HRMS</span>
            </a>
            <div class="hp-nav-links">
                <a href="/version2/homepage.php" class="hp-nav-link active">Home</a>
                <a href="/version2/app/guest/rooms" class="hp-nav-link">Rooms</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="/version2/auth/login.php" class="hp-nav-link hp-nav-btn">Login</a>
                <?php else: ?>
                    <a href="/version2/app/" class="hp-nav-link hp-nav-btn">Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hp-hero">
        <div class="hp-hero-bg-shapes">
            <div class="hp-shape hp-shape-1"></div>
            <div class="hp-shape hp-shape-2"></div>
            <div class="hp-shape hp-shape-3"></div>
            <div class="hp-shape hp-shape-4"></div>
            <div class="hp-shape hp-shape-5"></div>
        </div>
        <div class="container hp-hero-content">
            <span class="hp-hero-badge">★ Premium Hospitality</span>
            <h1 class="hp-hero-title">Welcome to<br><span class="hp-hero-highlight">Our Lobby</span></h1>
            <p class="hp-hero-subtitle">
                Experience luxury and comfort with our premium hotel room management system.
                Find your perfect stay with ease.
            </p>
            <div class="hp-hero-actions">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="/version2/auth/login.php" class="hp-btn hp-btn-primary">
                        <i class="fas fa-arrow-right"></i> Get Started
                    </a>
                    <a href="/version2/app/guest/rooms" class="hp-btn hp-btn-outline">
                        <i class="fas fa-door-open"></i> View Rooms
                    </a>
                <?php else: ?>
                    <a href="/version2/app/" class="hp-btn hp-btn-primary">
                        <i class="fas fa-th-large"></i> Go to Dashboard
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- Stats Counter Bar -->
    <section class="hp-stats-bar">
        <div class="container">
            <div class="hp-stats-grid">
                <div class="hp-stat-item hp-animate" data-count="200">
                    <div class="hp-stat-icon"><i class="fas fa-bed"></i></div>
                    <div class="hp-stat-number"><span class="hp-counter">0</span>+</div>
                    <div class="hp-stat-label">Luxury Rooms</div>
                </div>
                <div class="hp-stat-item hp-animate" data-count="5000">
                    <div class="hp-stat-icon"><i class="fas fa-smile"></i></div>
                    <div class="hp-stat-number"><span class="hp-counter">0</span>+</div>
                    <div class="hp-stat-label">Happy Guests</div>
                </div>
                <div class="hp-stat-item hp-animate" data-count="4.8" data-decimal="true">
                    <div class="hp-stat-icon"><i class="fas fa-star"></i></div>
                    <div class="hp-stat-number"><span class="hp-counter">0</span></div>
                    <div class="hp-stat-label">Guest Rating</div>
                </div>
                <div class="hp-stat-item hp-animate" data-count="24">
                    <div class="hp-stat-icon"><i class="fas fa-headset"></i></div>
                    <div class="hp-stat-number"><span class="hp-counter">0</span>/7</div>
                    <div class="hp-stat-label">Support Available</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="hp-features">
        <div class="container">
            <div class="hp-section-header hp-animate">
                <span class="hp-section-badge">Our Amenities</span>
                <h2 class="hp-section-title">Why Choose Us</h2>
                <p class="hp-section-subtitle">Everything you need for a perfect stay experience</p>
            </div>

            <div class="hp-features-grid">
                <div class="hp-feature-card hp-animate">
                    <div class="hp-feature-icon-wrap">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3>Free WiFi</h3>
                    <p>High-speed internet connectivity in all rooms and common areas</p>
                </div>

                <div class="hp-feature-card hp-animate">
                    <div class="hp-feature-icon-wrap">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <h3>Breakfast Included</h3>
                    <p>Complimentary breakfast buffet with fresh local ingredients</p>
                </div>

                <div class="hp-feature-card hp-animate">
                    <div class="hp-feature-icon-wrap">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>Free Parking</h3>
                    <p>Secure parking facility available for all our guests</p>
                </div>

                <div class="hp-feature-card hp-animate">
                    <div class="hp-feature-icon-wrap">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>24/7 Security</h3>
                    <p>Round-the-clock security service ensuring your safety</p>
                </div>

                <div class="hp-feature-card hp-animate">
                    <div class="hp-feature-icon-wrap">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Flexible Booking</h3>
                    <p>Easy booking process with flexible cancellation policies</p>
                </div>

                <div class="hp-feature-card hp-animate">
                    <div class="hp-feature-icon-wrap">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <h3>Premium Service</h3>
                    <p>Dedicated concierge and room service for a seamless experience</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="hp-testimonials">
        <div class="container">
            <div class="hp-section-header hp-animate">
                <span class="hp-section-badge">Guest Reviews</span>
                <h2 class="hp-section-title">What Our Guests Say</h2>
                <p class="hp-section-subtitle">Real experiences from our valued guests</p>
            </div>

            <div class="hp-testimonials-grid">
                <div class="hp-testimonial-card hp-animate">
                    <div class="hp-testimonial-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                            class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="hp-testimonial-text">"Absolutely wonderful stay! The rooms were spotless, the staff was
                        incredibly friendly, and the booking process was seamless."</p>
                    <div class="hp-testimonial-author">
                        <div class="hp-author-avatar"><i class="fas fa-user"></i></div>
                        <div>
                            <strong>Mr. Hari Prasad Acharya</strong>
                            <span>Business Traveler</span>
                        </div>
                    </div>
                </div>

                <div class="hp-testimonial-card hp-animate">
                    <div class="hp-testimonial-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                            class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="hp-testimonial-text">"The best hotel management system I've encountered. Easy to navigate,
                        great room selection, and the check-in was so smooth."</p>
                    <div class="hp-testimonial-author">
                        <img class="hp-author-avatar" src="/version2/uploads/Customers/IMG_20260616_164734.jpg" alt="Sambhawana Limbu" style="object-fit: cover;">
                        <div>
                            <strong>Ms. Sambhawana Limbu</strong>
                            <span>Family Vacation</span>
                        </div>
                    </div>
                </div>

                <div class="hp-testimonial-card hp-animate">
                    <div class="hp-testimonial-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                            class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="hp-testimonial-text">"Premium experience from start to finish. The amenities are top-notch
                        and the 24/7 support made everything effortless."</p>
                    <div class="hp-testimonial-author">
                        <div class="hp-author-avatar"><i class="fas fa-user"></i></div>
                        <div>
                            <strong>Mr. Khagenndra Raj Subedi</strong>
                            <span>Solo Explorer</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <?php if (!isset($_SESSION['user_id'])): ?>
        <section class="hp-cta">
            <div class="container">
                <div class="hp-cta-card hp-animate">
                    <h2>Ready to Book Your Stay?</h2>
                    <p>Create an account or login to book rooms, manage your bookings, and enjoy exclusive features.</p>
                    <div class="hp-cta-actions">
                        <a href="/version2/auth/login.php" class="hp-btn hp-btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="/version2/auth/signup.php" class="hp-btn hp-btn-outline-light">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="hp-footer">
        <div class="container">
            <div class="hp-footer-grid">
                <div class="hp-footer-col">
                    <div class="hp-footer-logo">
                        <div class="hp-logo-icon"><i class="fas fa-hotel"></i></div>
                        <span>HRMS</span>
                    </div>
                    <p class="hp-footer-desc">Your trusted partner for premium hotel room management. Experience comfort
                        and luxury at every stay.</p>
                </div>
                <div class="hp-footer-col">
                    <h4>Quick Links</h4>
                    <ul class="hp-footer-links">
                        <li><a href="/version2/homepage.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="/version2/app/guest/rooms"><i class="fas fa-chevron-right"></i> Browse Rooms</a>
                        </li>
                        <li><a href="/version2/auth/login.php"><i class="fas fa-chevron-right"></i> Login</a></li>
                        <li><a href="/version2/auth/signup.php"><i class="fas fa-chevron-right"></i> Register</a></li>
                    </ul>
                </div>
                <div class="hp-footer-col">
                    <h4>Contact Info</h4>
                    <ul class="hp-footer-contact">
                        <li><i class="fas fa-map-marker-alt"></i> Lainchaur, Kathmandu</li>
                        <li><i class="fas fa-phone"></i> +977 9803040024</li>
                        <li><i class="fas fa-envelope"></i> hotel@hrms.com</li>
                        <li><i class="fas fa-clock"></i> 24/7 Reception</li>
                    </ul>
                </div>
                <div class="hp-footer-col">
                    <h4>Follow Us</h4>
                    <div class="hp-social-links">
                        <a href="#" class="hp-social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="hp-social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="hp-social-icon"><i class="fab fa-twitter"></i></a>

                    </div>
                </div>
            </div>
            <div class="hp-footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Hotel Room Management System. All rights reserved by HRMS admin</p>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        const navbar = document.getElementById('hp-navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('hp-navbar-scrolled', window.scrollY > 50);
        });

        // Scroll-triggered animations via IntersectionObserver
        const animateEls = document.querySelectorAll('.hp-animate');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('hp-visible');
                    }, i * 100);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        animateEls.forEach(el => observer.observe(el));

        // Animated counters
        function animateCounter(el) {
            const target = parseFloat(el.parentElement.parentElement.dataset.count);
            const isDecimal = el.parentElement.parentElement.dataset.decimal === 'true';
            const duration = 2000;
            const start = performance.now();
            function update(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                // ease out cubic
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = eased * target;
                el.textContent = isDecimal ? current.toFixed(1) : Math.floor(current);
                if (progress < 1) requestAnimationFrame(update);
            }
            requestAnimationFrame(update);
        }

        const counterEls = document.querySelectorAll('.hp-counter');
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        counterEls.forEach(el => counterObserver.observe(el));
    </script>
</body>

</html>