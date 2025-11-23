<?php
// Direct API test - no frameworks
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Khalti API Direct Test</h2>";

$public_key = 'c2f3a0c0a3714fde883e4d57555f39c6';
$secret_key = '84e4e3ea15914437a2d581cf4fccb801';

echo "<p><strong>Testing with:</strong></p>";
echo "<p>Public Key: " . $public_key . "</p>";
echo "<p>Secret Key: " . substr($secret_key, 0, 10) . "...</p>";
echo "<hr>";

// Test data
$data = array(
    "return_url" => "http://localhost/version2/bookings.php",
    "website_url" => "http://localhost/version2/",
    "amount" => 1000,
    "purchase_order_id" => "test_" . time(),
    "purchase_order_name" => "Test Payment",
    "customer_info" => array(
        "name" => "Test User",
        "email" => "test@example.com",
        "phone" => "9800000000"
    )
);

echo "<h3>Request Payload:</h3>";
echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

// Try production API
echo "<h3>Testing Production API (khalti.com):</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://khalti.com/api/v2/epayment/initiate/");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_VERBOSE, 1);

$headers = [
    'Authorization: key ' . $secret_key,
    'Content-Type: application/json'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

echo "<p><strong>Headers being sent:</strong></p>";
echo "<pre>";
print_r($headers);
echo "</pre>";

$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Status Code:</strong> " . $status_code . "</p>";

if ($curl_error) {
    echo "<p style='color: red;'><strong>cURL Error:</strong> " . $curl_error . "</p>";
}

echo "<h4>API Response:</h4>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$response_data = json_decode($response, true);
if ($response_data) {
    echo "<h4>Parsed Response:</h4>";
    echo "<pre>";
    print_r($response_data);
    echo "</pre>";
}

echo "<hr>";
echo "<h3>Diagnosis:</h3>";
if ($status_code == 200 && isset($response_data['payment_url'])) {
    echo "<p style='color: green; font-size: 18px;'><strong>✓ SUCCESS!</strong> Your keys work!</p>";
    echo "<p><a href='" . $response_data['payment_url'] . "' target='_blank'>Click here to test payment page</a></p>";
} else if ($status_code == 401 || $status_code == 403) {
    echo "<p style='color: red; font-size: 18px;'><strong>✗ AUTHENTICATION FAILED</strong></p>";
    echo "<p>Your secret key is not valid or not activated for API access.</p>";
    echo "<p><strong>Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Verify your keys at <a href='https://admin.khalti.com' target='_blank'>https://admin.khalti.com</a></li>";
    echo "<li>Check if API access is enabled in your merchant settings</li>";
    echo "<li>Contact Khalti support if keys are correct but not working</li>";
    echo "</ul>";
} else {
    echo "<p style='color: orange;'><strong>⚠ ERROR</strong></p>";
    echo "<p>Check the response above for details</p>";
}
?>
