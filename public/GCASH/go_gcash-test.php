<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get reference from URL
$ref = $_GET['ref'] ?? '';

if (empty($ref)) {
    die("Reference number is required");
}

try {
    // Get payment parameters from session or database based on reference
    // For this example, we'll use the parameters directly
    $params = $_GET;
    
    // Build the payment request parameters
    $requestParams = http_build_query([
        'amount' => $params['amount'] ?? '',
        'invno' => $params['invno'] ?? '',
        'successUrl' => $params['successUrl'] ?? '',
        'currency' => 'PHP',
        'payflow' => $params['payflow'] ?? 'PH',
        'buyerName' => $params['buyerName'] ?? '',
        'buyerAddress' => $params['buyerAddress'] ?? '',
        'buyerCity' => $params['buyerCity'] ?? '',
        'buyerState' => $params['buyerState'] ?? '',
        'buyerPostalCode' => $params['buyerPostalCode'] ?? '',
        'buyerCountry' => $params['buyerCountry'] ?? 'PH',
        'buyerPhone' => $params['buyerPhone'] ?? '',
        'buyerEmail' => $params['buyerEmail'] ?? ''
    ]);

    // GCash API endpoint (replace with actual endpoint)
    $gcashEndpoint = 'https://api.gcash.com/payment'; // Example URL

    // Create payment request to GCash
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $gcashEndpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }

    // Parse GCash response
    $result = json_decode($response, true);

    if (isset($result['payment_url'])) {
        // Redirect to GCash payment page
        header("Location: " . $result['payment_url']);
        exit;
    } else {
        throw new Exception("Invalid response from GCash");
    }

} catch (Exception $e) {
    // Log error
    error_log("GCash Payment Error: " . $e->getMessage());
    
    // Display user-friendly error
    echo "<h2>Payment Error</h2>";
    echo "<p>Sorry, there was a problem processing your payment. Please try again later.</p>";
    echo "<p>Error details: " . $e->getMessage() . "</p>";
    
    // Add a back button
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
}