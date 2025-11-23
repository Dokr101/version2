<?php
require_once 'includes/config.php';

// Handle room booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
    requireLogin();
    requireGuest(); // Only guests can book rooms

    $room_id = $_POST['room_id'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $guests = $_POST['guests'];

    // Check if room is available for the dates
    $stmt = $pdo->prepare("
        SELECT * FROM bookings 
        WHERE room_id = ? 
        AND status IN ('pending', 'confirmed', 'checked_in')
        AND (
            (checkin <= ? AND checkout >= ?) OR 
            (checkin <= ? AND checkout >= ?) OR
            (checkin >= ? AND checkout <= ?)
        )
    ");
    $stmt->execute([$room_id, $checkin, $checkin, $checkout, $checkout, $checkin, $checkout]);
    $existing_booking = $stmt->fetch();

    if ($existing_booking) {
        $error = "Sorry, the room is not available for the selected dates.";
    } else {
        // Calculate total price
        $stmt = $pdo->prepare("SELECT price FROM rooms WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        $nights = (strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24);
        $total_price = $room['price'] * $nights;

        // Create booking
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, room_id, checkin, checkout, guests, total_price) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$_SESSION['user_id'], $room_id, $checkin, $checkout, $guests, $total_price])) {
            $_SESSION['success'] = "Room booked successfully! Please wait for confirmation.";
            header("Location: guest_dashboard.php");
            exit();
        } else {
            $error = "Booking failed. Please try again.";
        }
    }
}

// Get all available rooms
$stmt = $pdo->query("SELECT * FROM rooms WHERE status = 'available'");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique room types for filter
$room_types = [];
foreach ($rooms as $room) {
    if (!in_array($room['type'], $room_types)) {
        $room_types[] = $room['type'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Rooms - Hotel MS</title>
    <link rel="stylesheet" href="/version2/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="main-content">
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Sidebar for logged-in users -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-circle">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="logo-text">Hotel MS</div>
                <div class="logo-subtitle"><?php echo isAdmin() ? 'Admin Panel' : (isStaff() ? 'Staff Panel' : 'Guest Portal'); ?></div>
            </div>
            <ul class="sidebar-menu">
                <?php if (isAdmin()): ?>
                    <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="manage_staff.php"><i class="fas fa-users-cog"></i> Manage Staff</a></li>
                    <li><a href="manage_rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                    <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                    <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payment Records</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php elseif (isStaff()): ?>
                    <li><a href="staff_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="staff_checkin.php"><i class="fas fa-sign-in-alt"></i> Check-in</a></li>
                    <li><a href="staff_checkout.php"><i class="fas fa-sign-out-alt"></i> Check-out</a></li>
                    <li><a href="staff_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a></li>
                    <li><a href="staff_payments.php"><i class="fas fa-credit-card"></i> Process Payments</a></li>
                    <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <?php else: ?>
                    <li><a href="guest_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="rooms.php" class="active"><i class="fas fa-bed"></i> Book Rooms</a></li>
                    <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <?php endif; ?>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        <?php endif; ?>

        <!-- Content Area -->
        <main class="content" style="<?php echo !isset($_SESSION['user_id']) ? 'margin-left: 0; width: 100%;' : ''; ?>">
            <div class="page-header">
                <h1>Our Rooms</h1>
                <p>Discover our comfortable and luxurious accommodations</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <!-- Filter Section -->
            <section class="card">
                <h2 style="margin-bottom: 20px;">Filter Rooms</h2>
                <div class="filter-controls" style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label for="type-filter">Room Type:</label>
                        <select id="type-filter" class="form-control" onchange="filterRooms()">
                            <option value="">All Types</option>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label for="price-filter">Price Range:</label>
                        <select id="price-filter" class="form-control" onchange="filterRooms()">
                            <option value="">All Prices</option>
                            <option value="low">Under Rs.150</option>
                            <option value="medium">Rs.150 - Rs.250</option>
                            <option value="high">Over Rs.250</option>
                        </select>
                    </div>
                    <?php if (isset($_SESSION['user_id']) && isGuest()): ?>
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label for="availability-check">Quick Check:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="date" id="quick-checkin" class="form-control" placeholder="Check-in">
                            <input type="date" id="quick-checkout" class="form-control" placeholder="Check-out">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Rooms Grid -->
            <div class="rooms-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; margin-top: 30px;">
                <?php foreach ($rooms as $room): ?>
                    <div class="card room-card" data-type="<?php echo $room['type']; ?>" data-price="<?php echo $room['price']; ?>">
                        <h3 style="color: var(--primary); margin-bottom: 10px;"><?php echo $room['type']; ?> Room</h3>
                        <p class="price" style="font-size: 1.5rem; font-weight: bold; color: var(--success); margin-bottom: 15px;">
                            Rs.<?php echo $room['price']; ?>/night
                        </p>
                        <p style="color: var(--secondary); margin-bottom: 20px; line-height: 1.6;">
                            <?php echo $room['description']; ?>
                        </p>
                        <p style="color: var(--secondary); margin-bottom: 15px;">
                            <strong>Amenities:</strong> <?php echo $room['amenities']; ?>
                        </p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                            <span class="status available">Available</span>
                            
                            <?php if (isLoggedIn() && isGuest()): ?>
                                <button class="btn btn-primary book-now-btn" 
                                        data-room-id="<?php echo $room['room_id']; ?>"
                                        data-room-type="<?php echo $room['type']; ?>"
                                        data-room-price="<?php echo $room['price']; ?>">
                                    Book Now
                                </button>
                            <?php elseif (!isLoggedIn()): ?>
                                <a href="auth/login.php" class="btn btn-primary">Login to Book</a>
                            <?php endif; ?>
                        </div>

                        <?php if (isLoggedIn() && isGuest()): ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--gray-light);">
                            <small style="color: var(--secondary);">
                                <i class="fas fa-info-circle"></i> Click "Book Now" to select dates and complete booking
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($rooms)): ?>
                <div class="card" style="text-align: center; padding: 40px;">
                    <h3 style="color: #6c757d; margin-bottom: 15px;">No Rooms Available</h3>
                    <p style="color: #6c757d;">All our rooms are currently booked. Please check back later.</p>
                    <?php if (isAdmin()): ?>
                        <a href="manage_rooms.php" class="btn btn-primary">Add New Rooms</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Compact Booking Modal -->
    <?php if (isLoggedIn() && isGuest()): ?>
    <div id="bookingModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            
            <div class="modal-header">
                <h2>Book Room</h2>
            </div>

            <form id="bookingForm" method="POST" class="compact-form">
                <input type="hidden" name="room_id" id="modal_room_id">
                
                <div class="form-group">
                    <label for="room_type">Room Type:</label>
                    <input type="text" id="room_type" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label for="room_price">Price per Night:</label>
                    <input type="text" id="room_price" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label for="guests">Number of Guests:</label>
                    <select id="guests" name="guests" class="form-control" required>
                        <option value="1">1 Guest</option>
                        <option value="2">2 Guests</option>
                        <option value="3">3 Guests</option>
                        <option value="4">4 Guests</option>
                    </select>
                </div>
                
                <div class="date-inputs-compact">
                    <div class="form-group">
                        <label for="checkin">Check-in Date:</label>
                        <input type="date" id="checkin" name="checkin" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="checkout">Check-out Date:</label>
                        <input type="date" id="checkout" name="checkout" class="form-control" required>
                    </div>
                </div>
                
                <div class="summary-box-compact">
                    <div class="summary-item-compact">
                        <span>Number of Nights:</span>
                        <span id="total_nights_text">0</span>
                    </div>
                    <div class="summary-item-compact summary-total-compact">
                        <span>Total Price:</span>
                        <span id="total_price_text">Rs. 0</span>
                    </div>
                </div>
                
                <div class="btn-group-compact">
                    <button type="button" class="btn btn-outline" onclick="closeModal('bookingModal')">Cancel</button>
                    <button type="submit" name="book_room" class="btn btn-primary" id="confirmBookingBtn" disabled>
                        Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isLoggedIn() && isGuest()): ?>
            // Modal functionality
            const modal = document.getElementById('bookingModal');
            const closeBtn = document.querySelector('.close');
            const bookButtons = document.querySelectorAll('.book-now-btn');
            const checkinInput = document.getElementById('checkin');
            const checkoutInput = document.getElementById('checkout');

            // Book Now button click handler
            bookButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const roomId = this.getAttribute('data-room-id');
                    const roomType = this.getAttribute('data-room-type');
                    const roomPrice = this.getAttribute('data-room-price');
                    
                    document.getElementById('modal_room_id').value = roomId;
                    document.getElementById('room_type').value = roomType + ' Room';
                    document.getElementById('room_price').value = 'Rs.' + roomPrice + '/night';
                    
                    // Reset form
                    document.getElementById('checkin').value = '';
                    document.getElementById('checkout').value = '';
                    document.getElementById('guests').value = '1';
                    document.getElementById('total_nights_text').textContent = '0';
                    document.getElementById('total_price_text').textContent = 'Rs. 0';
                    document.getElementById('confirmBookingBtn').disabled = true;
                    
                    // Set minimum date to today
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('checkin').min = today;
                    
                    modal.style.display = 'block';
                });
            });

            // Close modal handlers
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });

            // Price calculation function
            function calculatePrice() {
                const checkinInput = document.getElementById('checkin');
                const checkoutInput = document.getElementById('checkout');
                const confirmBtn = document.getElementById('confirmBookingBtn');
                
                if (checkinInput.value && checkoutInput.value) {
                    const checkin = new Date(checkinInput.value);
                    const checkout = new Date(checkoutInput.value);
                    const nights = (checkout - checkin) / (1000 * 60 * 60 * 24);
                    
                    if (nights > 0) {
                        // Get room price from the button that was clicked
                        const roomPrice = parseFloat(document.getElementById('room_price').value.replace('Rs.', '').replace('/night', ''));
                        const totalPrice = roomPrice * nights;
                        
                        document.getElementById('total_nights_text').textContent = nights + ' night' + (nights > 1 ? 's' : '');
                        document.getElementById('total_price_text').textContent = 'Rs.' + totalPrice.toFixed(2);
                        confirmBtn.disabled = false;
                    } else {
                        document.getElementById('total_nights_text').textContent = '0 nights';
                        document.getElementById('total_price_text').textContent = 'Rs. 0';
                        confirmBtn.disabled = true;
                    }
                } else {
                    document.getElementById('total_nights_text').textContent = '0 nights';
                    document.getElementById('total_price_text').textContent = 'Rs. 0';
                    confirmBtn.disabled = true;
                }
            }

            // Add event listeners for date inputs
            if (checkinInput && checkoutInput) {
                checkinInput.addEventListener('change', function() {
                    const tomorrow = new Date(this.value);
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    const tomorrowStr = tomorrow.toISOString().split('T')[0];
                    
                    checkoutInput.min = tomorrowStr;
                    if (checkoutInput.value && checkoutInput.value <= this.value) {
                        checkoutInput.value = '';
                    }
                    calculatePrice();
                });

                checkoutInput.addEventListener('change', calculatePrice);
            }
            <?php endif; ?>

            // Filter rooms function
            function filterRooms() {
                const typeFilter = document.getElementById('type-filter').value;
                const priceFilter = document.getElementById('price-filter').value;
                
                const roomCards = document.querySelectorAll('.room-card');
                let visibleCount = 0;
                
                roomCards.forEach(card => {
                    const roomType = card.dataset.type;
                    const roomPrice = parseFloat(card.dataset.price);
                    
                    let show = true;
                    
                    if (typeFilter && roomType !== typeFilter) {
                        show = false;
                    }
                    
                    if (priceFilter === 'low' && roomPrice >= 150) {
                        show = false;
                    } else if (priceFilter === 'medium' && (roomPrice < 150 || roomPrice > 250)) {
                        show = false;
                    } else if (priceFilter === 'high' && roomPrice <= 250) {
                        show = false;
                    }
                    
                    card.style.display = show ? 'block' : 'none';
                    if (show) visibleCount++;
                });

                // Show message if no rooms match filters
                const roomsGrid = document.querySelector('.rooms-grid');
                let noResultsMsg = document.getElementById('no-results-message');
                
                if (visibleCount === 0) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.id = 'no-results-message';
                        noResultsMsg.className = 'card';
                        noResultsMsg.style.textAlign = 'center';
                        noResultsMsg.style.padding = '40px';
                        noResultsMsg.style.gridColumn = '1 / -1';
                        noResultsMsg.innerHTML = `
                            <h3 style="color: #6c757d; margin-bottom: 15px;">No Rooms Match Your Filters</h3>
                            <p style="color: #6c757d; margin-bottom: 20px;">Try adjusting your filters to see more options.</p>
                            <button class="btn btn-outline" onclick="resetFilters()">Reset Filters</button>
                        `;
                        roomsGrid.appendChild(noResultsMsg);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }

            // Make functions available globally
            window.filterRooms = filterRooms;
            window.resetFilters = function() {
                document.getElementById('type-filter').value = '';
                document.getElementById('price-filter').value = '';
                filterRooms();
            };
            window.closeModal = function(modalId) {
                document.getElementById(modalId).style.display = 'none';
            };

            // Quick date validation for non-logged-in users
            const quickCheckin = document.getElementById('quick-checkin');
            const quickCheckout = document.getElementById('quick-checkout');
            
            if (quickCheckin && quickCheckout) {
                const today = new Date().toISOString().split('T')[0];
                quickCheckin.min = today;
                
                quickCheckin.addEventListener('change', function() {
                    quickCheckout.min = this.value;
                });
            }
        });
    </script>
</body>
</html>