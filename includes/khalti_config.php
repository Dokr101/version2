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

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Set to true to use live keys and production endpoint, or false for testing/sandbox
define('KHALTI_LIVE_MODE', defined('KHALTI_LIVE_MODE_LOCAL') ? KHALTI_LIVE_MODE_LOCAL : false);
define('KHALTI_PUBLIC_KEY', defined('KHALTI_PUBLIC_KEY_LOCAL') ? KHALTI_PUBLIC_KEY_LOCAL : 'c2f3a0c0a3714fde883e4d57555f39c6');
define('KHALTI_SECRET_KEY', defined('KHALTI_SECRET_KEY_LOCAL') ? KHALTI_SECRET_KEY_LOCAL : '84e4e3ea15914437a2d581cf4fccb801');

// Payment configuration
define('KHALTI_MERCHANT_NAME', 'Hotel Room Management System');
define('KHALTI_CURRENCY', 'NPR'); // Nepalese Rupee
?>
