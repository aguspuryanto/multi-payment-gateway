<?php

namespace MultiPaymentGateway\Gateways;

class CCD implements GatewayInterface
{
    public function processPayment(float $amount, string $currency): array
    {
        // Simulasi proses pembayaran
        $transactionId = uniqid('CCD-');
        
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'message' => "Processing Credit Card Payment for {$amount} {$currency}",
            'amount' => $amount,
            'currency' => $currency
        ];
    }

    public function getStatus(string $transactionId): array
    {
        return [
            'transaction_id' => $transactionId,
            'status' => 'completed',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
} 