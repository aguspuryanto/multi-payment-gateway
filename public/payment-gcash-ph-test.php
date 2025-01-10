<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MultiPaymentGateway\PaymentManager;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$baseUrl = 'https://ew-dev.dxn2u.com/ajax-api/';
$config = [];

try {
    // Initialize PaymentManager
    $paymentManager = new PaymentManager('GCSH', 'PH', $baseUrl, $config);

    // Process payment
    $result = $paymentManager->process(1000.00, 'PHP', [
        'gateway_code' => 'GCSH',
        'invoice_no' => 'INV-' . time(),
        'buyer_name' => 'Juan Dela Cruz',
        'buyer_address' => '123 Sample Street',
        'buyer_city' => 'Makati',
        'buyer_state' => 'Metro Manila',
        'buyer_postal_code' => '1200',
        'buyer_country' => 'PH',
        'buyer_phone' => '+639123456789',
        'buyer_email' => 'juan@example.com'
    ]);

    // Display results
    echo "<h2>GCash Payment Processing Result:</h2>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";

    if ($result['success']) {
        echo "<h3>Payment URL:</h3>";
        echo "<a href='{$result['payment_url']}' target='_blank'>Proceed to GCash Payment</a>";
        
        echo "<hr>";
        echo "<h3>Check Transaction Status:</h3>";
        echo "<p>Transaction ID: {$result['transaction_id']}</p>";
        echo "<button onclick='checkStatus(\"{$result['transaction_id']}\")'>Check Status</button>";
        
        echo "
        <script>
        function checkStatus(transactionId) {
            fetch('payment-gcash-ph-status.php?transaction_id=' + transactionId)
                .then(response => response.json())
                .then(data => {
                    alert('Transaction Status: ' + data.status);
                })
                .catch(error => {
                    alert('Error checking status: ' + error);
                });
        }
        </script>";
    }

} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<pre>";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString();
    echo "</pre>";
} 