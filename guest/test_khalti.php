<?php
// Simple test to check if Khalti API is working
require_once '../includes/config.php';
require_once '../includes/khalti_config.php';

echo "<h2>Khalti API Test</h2>";
echo "<p><strong>Public Key:</strong> " . KHALTI_PUBLIC_KEY . "</p>";
echo "<p><strong>Secret Key:</strong> " . substr(KHALTI_SECRET_KEY, 0, 20) . "...</p>";
echo "<hr>";

// Test API call
$data = array(
    "return_url" => "http://localhost/version2/bookings.php",
    "website_url" => "http://localhost/version2/",
    "amount" => 1000,
    "purchase_order_id" => "test_" . time(),
    "purchase_order_name" => "Test Order",
    "customer_info" => array(
        "name" => "Test User",
        "email" => "test@example.com",
        "phone" => "9800000000"
    )
);

echo "<h3>Request Data:</h3>";
echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://a.khalti.com/api/v2/epayment/initiate/");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$headers = [
    'Authorization: key ' . KHALTI_SECRET_KEY,
    'Content-Type: application/json'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<h3>API Response:</h3>";
echo "<p><strong>Status Code:</strong> " . $status_code . "</p>";

if ($curl_error) {
    echo "<p style='color: red;'><strong>cURL Error:</strong> " . $curl_error . "</p>";
}

echo "<h4>Response Body:</h4>";
echo "<pre>" . $response . "</pre>";

$response_data = json_decode($response, true);
if ($response_data) {
    echo "<h4>Parsed Response:</h4>";
    echo "<pre>" . print_r($response_data, true) . "</pre>";
    
    if (isset($response_data['payment_url'])) {
        echo "<p style='color: green;'><strong>✓ Success! Payment URL received:</strong> " . $response_data['payment_url'] . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Error: No payment_url in response</strong></p>";
    }
}
?>
