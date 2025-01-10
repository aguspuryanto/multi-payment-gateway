<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MultiPaymentGateway\PaymentManager;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$baseUrl = 'https://ew-dev.dxn2u.com/ajax-api/';
$config = [];

try {
    // Initialize PaymentManager
    $paymentManager = new PaymentManager('CCD', 'MY', $baseUrl, $config);

    // Process payment
    // $params = "invNo=$mb_invno&amt=$mb_edisc_amt&memcode=$mb_code&cn_id=$mb_shipcnt&cctype=$mb_cctype";
    $result = $paymentManager->process(100.00, 'MYR', [
        'gateway_code' => 'CCD',
        'invoice_no' => 'INV-' . time(), // Generate unique invoice number
        'member_code' => 'MEM001',
        'country_id' => 'MY',
        'cc_type' => 'VISA'
    ]);

    // Display results in a readable format
    echo "<h2>Payment Processing Result:</h2>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";

    if ($result['success']) {
        echo "<h3>Payment URL:</h3>";
        echo "<a href='{$result['payment_url']}' target='_blank'>Click here to proceed to payment page</a>";
        
        // Store transaction ID for status check
        echo "<hr>";
        echo "<h3>Check Transaction Status:</h3>";
        echo "<p>Transaction ID: {$result['transaction_id']}</p>";
        
        // Add status check button
        echo "<button onclick='checkStatus(\"{$result['transaction_id']}\")'>Check Status</button>";
        
        // Add JavaScript for status check
        echo "
        <script>
        function checkStatus(transactionId) {
            fetch('payment-ccd-my-status.php?transaction_id=' + transactionId)
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