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
    $config = [
        'client_id' => 'AWMUDLVNmrBa-Xex4119Enva9tlaHi2WfPM1SDm_yzG0_OS8BQ2P0exWNHXeFAgAhq6FsY10trLk1z4k',
        'client_secret' => 'ENoMZBW0hg7f8ppiO-xKh1Ep0u1Ns7mOoDaxXlT-8Ahx8VSrgLGD_IwjRk2eDKxyaKz3HOLq-ELsl4XM'
    ];

    $paymentManager = new PaymentManager('PYPL', 'US', $baseUrl, $config);
    $status = $paymentManager->getTransactionStatus($transactionId);

    echo json_encode($status);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 