<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MultiPaymentGateway\PaymentManager;

header('Content-Type: application/json');

try {
    $transactionId = $_GET['transaction_id'] ?? null;
    
    if (!$transactionId) {
        throw new Exception('Transaction ID is required');
    }

    $baseUrl = 'https://ew-dev.dxn2u.com/ajax-api/';
    $config = [];

    $paymentManager = new PaymentManager('CCD', 'MY', $baseUrl, $config);
    $status = $paymentManager->getTransactionStatus($transactionId);

    echo json_encode($status);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 