<?php
/**
 * Khalti Payment Gateway Configuration
 * 
 * This file contains configuration for Khalti payment integration.
 * Khalti is a popular payment gateway in Nepal.
 */

// ========================================
// TEST/SANDBOX MODE - TEST KEYS
// ========================================
// These keys work with sandbox endpoint: a.khalti.com
// For production, replace with live keys and use khalti.com endpoint
// ========================================

// TEST KEYS (SANDBOX - For testing only)
define('KHALTI_PUBLIC_KEY', 'c2f3a0c0a3714fde883e4d57555f39c6');
define('KHALTI_SECRET_KEY', '84e4e3ea15914437a2d581cf4fccb801');

// Payment configuration
define('KHALTI_MERCHANT_NAME', 'Hotel Management System');
define('KHALTI_CURRENCY', 'NPR'); // Nepalese Rupee
?>
