// Main JavaScript for HRMS
document.addEventListener('DOMContentLoaded', function() {
    // Authentication Form Switcher
    const authTabs = document.querySelectorAll('.auth-tab');
    const authForms = document.querySelectorAll('.auth-form');
    
    if (authTabs.length > 0) {
        authTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                
                // Remove active class from all tabs and forms
                authTabs.forEach(t => t.classList.remove('active'));
                authForms.forEach(f => f.classList.remove('active'));
                
                // Add active class to current tab and form
                this.classList.add('active');
                document.getElementById(target).classList.add('active');
            });
        });
    }
    
    // Form validation for login
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            if (!validateEmail(email)) {
                showAlert('Please enter a valid email address', 'error');
                return;
            }
            
            if (password.length < 6) {
                showAlert('Password must be at least 6 characters', 'error');
                return;
            }
            
            // Submit form if validation passes
            this.submit();
        });
    }
    
    // Form validation for signup
    const signupForm = document.getElementById('signup-form');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('signup-name').value;
            const email = document.getElementById('signup-email').value;
            const password = document.getElementById('signup-password').value;
            const confirmPassword = document.getElementById('signup-confirm-password').value;
            
            if (name.trim().length < 2) {
                showAlert('Please enter your full name', 'error');
                return;
            }
            
            if (!validateEmail(email)) {
                showAlert('Please enter a valid email address', 'error');
                return;
            }
            
            if (password.length < 6) {
                showAlert('Password must be at least 6 characters', 'error');
                return;
            }
            
            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'error');
                return;
            }
            
            // Submit form if validation passes
            this.submit();
        });
    }
    
    // Date validation for booking forms
    const checkinInput = document.getElementById('checkin');
    const checkoutInput = document.getElementById('checkout');
    
    if (checkinInput && checkoutInput) {
        const today = new Date().toISOString().split('T')[0];
        checkinInput.min = today;
        
        checkinInput.addEventListener('change', function() {
            checkoutInput.min = this.value;
            if (checkoutInput.value && checkoutInput.value < this.value) {
                checkoutInput.value = '';
            }
        });
    }
    
    // Room availability check
    const checkAvailabilityBtn = document.getElementById('check-availability');
    if (checkAvailabilityBtn) {
        checkAvailabilityBtn.addEventListener('click', checkRoomAvailability);
    }
    
    // Payment simulation
    const payButtons = document.querySelectorAll('.pay-btn');
    payButtons.forEach(btn => {
        btn.addEventListener('click', simulatePayment);
    });
    
    // Modal functionality
    initializeModals();
    
    // Initialize any other components
    initializeComponents();
});

// Utility Functions
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    // Insert at the top of the form container
    const formContainer = document.querySelector('.auth-container') || document.querySelector('.form-container');
    if (formContainer) {
        formContainer.insertBefore(alertDiv, formContainer.firstChild);
        
        // Remove alert after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Room Availability Check
function checkRoomAvailability() {
    const checkin = document.getElementById('checkin').value;
    const checkout = document.getElementById('checkout').value;
    const roomType = document.getElementById('room-type').value;
    
    if (!checkin || !checkout) {
        showAlert('Please select both check-in and check-out dates', 'error');
        return;
    }
    
    // Show loading state
    const resultsDiv = document.getElementById('availability-results');
    resultsDiv.innerHTML = '<div class="alert">Checking availability...</div>';
    
    // Simulate AJAX request
    setTimeout(() => {
        const isAvailable = Math.random() > 0.3; // 70% chance of availability
        
        if (isAvailable) {
            resultsDiv.innerHTML = `
                <div class="alert alert-success">
                    Room is available! 
                    <button class="btn btn-primary" style="margin-left: 10px;">Book Now</button>
                </div>
            `;
        } else {
            resultsDiv.innerHTML = `
                <div class="alert alert-error">
                    Sorry, no rooms available for the selected dates.
                </div>
            `;
        }
    }, 1500);
}

// Payment Simulation
function simulatePayment(event) {
    const bookingId = event.target.getAttribute('data-booking-id');
    
    if (confirm('Confirm payment for this booking?')) {
        // Show loading state
        event.target.textContent = 'Processing...';
        event.target.disabled = true;
        
        // Simulate payment processing
        setTimeout(() => {
            showAlert('Payment confirmed! Booking status updated.', 'success');
            event.target.textContent = 'Pay Now';
            event.target.disabled = false;
            
            // In a real app, this would reload the page or update the UI
            location.reload();
        }, 2000);
    }
}

// Modal Management
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
}

// Open modal function
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

// Close modal function
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Room Filtering
function filterRooms() {
    const typeFilter = document.getElementById('type-filter').value;
    const priceFilter = document.getElementById('price-filter').value;
    
    const roomCards = document.querySelectorAll('.room-card');
    roomCards.forEach(card => {
        const roomType = card.dataset.type;
        const roomPrice = parseFloat(card.dataset.price);
        
        let show = true;
        
        if (typeFilter && roomType !== typeFilter) {
            show = false;
        }
        
        if (priceFilter === 'low' && roomPrice > 150) {
            show = false;
        } else if (priceFilter === 'medium' && (roomPrice <= 150 || roomPrice > 250)) {
            show = false;
        } else if (priceFilter === 'high' && roomPrice <= 250) {
            show = false;
        }
        
        card.style.display = show ? 'block' : 'none';
    });
}

// Initialize various components
function initializeComponents() {
    // Auto-hide alerts after 5 seconds
    const autoAlerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    autoAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }, 5000);
    });
    
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    });
}

// Make functions available globally
window.filterRooms = filterRooms;
window.openModal = openModal;
window.closeModal = closeModal;

