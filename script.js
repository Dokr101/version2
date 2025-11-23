// Main JavaScript for Hotel Management System
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeModals();
    initializeForms();
    initializeDatePickers();
    initializeFilters();
    
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
});

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

// Form Initialization
function initializeForms() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Re-enable button after 5 seconds (in case of error)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 5000);
            }
        });
    });
}

// Date Picker Initialization
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    
    dateInputs.forEach(input => {
        if (!input.min) {
            input.min = today;
        }
    });
    
    // Check-in/Check-out date validation
    const checkinInputs = document.querySelectorAll('input[name="checkin"]');
    const checkoutInputs = document.querySelectorAll('input[name="checkout"]');
    
    checkinInputs.forEach(checkin => {
        checkin.addEventListener('change', function() {
            const tomorrow = new Date(this.value);
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];
            
            checkoutInputs.forEach(checkout => {
                checkout.min = tomorrowStr;
                if (checkout.value && checkout.value <= this.value) {
                    checkout.value = '';
                }
            });
        });
    });
}

// Filter Initialization
function initializeFilters() {
    const filterSelects = document.querySelectorAll('select[onchange*="filter"]');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            if (typeof window[this.getAttribute('onchange').split('(')[0]] === 'function') {
                eval(this.getAttribute('onchange'));
            }
        });
    });
}

// Room Filtering Function
function filterRooms() {
    const typeFilter = document.getElementById('type-filter')?.value || '';
    const priceFilter = document.getElementById('price-filter')?.value || '';
    
    const roomCards = document.querySelectorAll('.room-card');
    let visibleCount = 0;
    
    roomCards.forEach(card => {
        const roomType = card.dataset.type;
        const roomPrice = parseFloat(card.dataset.price);
        
        let show = true;
        
        // Type filter
        if (typeFilter && roomType !== typeFilter) {
            show = false;
        }
        
        // Price filter
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
            if (roomsGrid) {
                roomsGrid.appendChild(noResultsMsg);
            }
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

// Reset Filters Function
function resetFilters() {
    const typeFilter = document.getElementById('type-filter');
    const priceFilter = document.getElementById('price-filter');
    
    if (typeFilter) typeFilter.value = '';
    if (priceFilter) priceFilter.value = '';
    
    filterRooms();
}

// Open Modal Function
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

// Close Modal Function
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Price Calculation for Booking Modal
function calculateBookingPrice() {
    const checkin = document.getElementById('checkin')?.value;
    const checkout = document.getElementById('checkout')?.value;
    const roomPriceElem = document.getElementById('room_price');
    const totalNightsElem = document.getElementById('total_nights_text');
    const totalPriceElem = document.getElementById('total_price_text');
    const confirmBtn = document.getElementById('confirmBookingBtn');
    
    if (!checkin || !checkout || !roomPriceElem || !totalNightsElem || !totalPriceElem || !confirmBtn) {
        return;
    }
    
    if (checkin && checkout) {
        const checkinDate = new Date(checkin);
        const checkoutDate = new Date(checkout);
        const nights = (checkoutDate - checkinDate) / (1000 * 60 * 60 * 24);
        
        if (nights > 0) {
            const roomPrice = parseFloat(roomPriceElem.value.replace('Rs.', '').replace('/night', ''));
            const totalPrice = roomPrice * nights;
            
            totalNightsElem.textContent = nights + ' night' + (nights > 1 ? 's' : '');
            totalPriceElem.textContent = 'Rs.' + totalPrice.toFixed(2);
            confirmBtn.disabled = false;
        } else {
            totalNightsElem.textContent = '0 nights';
            totalPriceElem.textContent = 'Rs. 0';
            confirmBtn.disabled = true;
        }
    } else {
        totalNightsElem.textContent = '0 nights';
        totalPriceElem.textContent = 'Rs. 0';
        confirmBtn.disabled = true;
    }
}

// AJAX Helper Functions
function makeRequest(url, data, method = 'POST') {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Error:', error);
        return { success: false, message: 'Network error occurred' };
    });
}

// Room Availability Check
function checkRoomAvailability(roomId, checkin, checkout) {
    return makeRequest('includes/functions.php?action=check_availability', {
        room_id: roomId,
        checkin: checkin,
        checkout: checkout
    });
}

// Export functions to global scope
window.filterRooms = filterRooms;
window.resetFilters = resetFilters;
window.openModal = openModal;
window.closeModal = closeModal;
window.calculateBookingPrice = calculateBookingPrice;