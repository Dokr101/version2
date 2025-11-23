<?php
require_once '../includes/config.php';
require_once '../includes/khalti_config.php';

echo "<h2>Khalti Configuration Debug</h2>";
echo "<p><strong>Public Key:</strong> " . KHALTI_PUBLIC_KEY . "</p>";
echo "<p><strong>Secret Key:</strong> " . substr(KHALTI_SECRET_KEY, 0, 10) . "..." . "</p>";
echo "<p><strong>Key Length (Public):</strong> " . strlen(KHALTI_PUBLIC_KEY) . "</p>";
echo "<p><strong>Key Length (Secret):</strong> " . strlen(KHALTI_SECRET_KEY) . "</p>";

echo "<hr>";
echo "<h3>Important Notes:</h3>";
echo "<ul>";
echo "<li><strong>For localhost/development:</strong> You MUST use TEST keys from Khalti</li>";
echo "<li><strong>For production (live domain):</strong> Use LIVE keys</li>";
echo "<li>Current keys appear to be: <span style='color: red;'>LIVE keys</span></li>";
echo "<li>This is why you're getting the 'Invalid key' error</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>Solution:</h3>";
echo "<p>1. Login to your Khalti Merchant Dashboard</p>";
echo "<p>2. Navigate to Settings → API Keys</p>";
echo "<p>3. Look for <strong>TEST Public Key</strong> and <strong>TEST Secret Key</strong></p>";
echo "<p>4. Replace the keys in: <code>/includes/khalti_config.php</code></p>";
?>
