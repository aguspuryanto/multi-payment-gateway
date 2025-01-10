<?php

namespace MultiPaymentGateway\Gateways;

class GCSH
{
    protected $baseUrl;
    protected $config;

    public function __construct($baseUrl, $config = [])
    {
        $this->baseUrl = $baseUrl;
        $this->config = $config;
    }

    public function processPayment($amount, $currency, $params = [])
    {
        // Format amount to 2 decimal places
        $amount = number_format($amount, 2, '.', '');

        // Build payment parameters
        $paymentParams = [
            'amount' => $amount,
            'invno' => $params['invoice_no'] ?? '',
            'successUrl' => $this->baseUrl . 'payment/GCSH/return_success.php',
            'currency' => 'PHP',
            'payflow' => 'PH',
            'buyerName' => $params['buyer_name'] ?? '',
            'buyerAddress' => $params['buyer_address'] ?? '',
            'buyerCity' => $params['buyer_city'] ?? '',
            'buyerState' => $params['buyer_state'] ?? '',
            'buyerPostalCode' => $params['buyer_postal_code'] ?? '',
            'buyerCountry' => $params['buyer_country'] ?? 'PH',
            'buyerPhone' => $params['buyer_phone'] ?? '',
            'buyerEmail' => $params['buyer_email'] ?? ''
        ];

        // Generate reference number
        $reference = uniqid('GCSH', true);

        // Build payment URL
        $paymentUrl = $this->baseUrl . "payment/GCSH/go_gcash.php?ref=" . $reference;

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_id' => $reference,
            'params' => $paymentParams
        ];
    }

    public function getTransactionStatus($transactionId)
    {
        // Implement status check logic here
        // This would typically involve calling GCash's API
        return [
            'status' => 'pending',
            'transaction_id' => $transactionId,
            'gateway' => 'GCSH'
        ];
    }
} 