<?php

namespace MultiPaymentGateway;

use MultiPaymentGateway\Gateways\GatewayInterface;
use MultiPaymentGateway\Payflows\PayflowInterface;
use RuntimeException;

class PaymentManager
{
    private ?GatewayInterface $gateway;
    private ?PayflowInterface $payflow;
    private string $baseUrl;
    private array $config;

    public function __construct(string $gatewayCode, string $payflowCode, string $baseUrl = '', array $config = [])
    {
        $this->gateway = $this->getGateway($gatewayCode);
        $this->payflow = $this->getPayflow($payflowCode);
        $this->baseUrl = $baseUrl;
        $this->config = $config;
    }

    private function getGateway(string $code): GatewayInterface
    {
        $gateway = "MultiPaymentGateway\\Gateways\\{$code}";
        
        if (!class_exists($gateway)) {
            throw new RuntimeException("Payment gateway {$code} not found");
        }

        return new $gateway();
    }

    private function getPayflow(string $code): PayflowInterface
    {
        $payflow = "MultiPaymentGateway\\Payflows\\{$code}";
        
        if (!class_exists($payflow)) {
            throw new RuntimeException("Payflow {$code} not found");
        }

        return new $payflow();
    }

    public function process(float $amount, string $currency, array $additionalData = []): array
    {
        if (!$this->payflow->validateCurrency($currency)) {
            throw new RuntimeException("Invalid currency for selected payflow");
        }

        $config = $this->payflow->getConfig();
        $result = $this->gateway->processPayment($amount, $currency);

        // Generate payment URL based on gateway type
        $paymentUrl = $this->generatePaymentUrl($amount, $additionalData);
        
        return array_merge($result, [
            'tax_rate' => $config['tax_rate'],
            'tax_amount' => $amount * $config['tax_rate'],
            'payment_url' => $paymentUrl
        ]);
    }

    private function generatePaymentUrl(float $amount, array $data): string
    {
        $xrandom = uniqid('PAY-');
        $gatewayCode = $data['gateway_code'] ?? '';
        
        switch ($gatewayCode) {
            case 'CCD': // Credit Card
                $params = http_build_query([
                    'invNo' => $data['invoice_no'] ?? '',
                    'amt' => $amount,
                    'memcode' => $data['member_code'] ?? '',
                    'cn_id' => $data['country_id'] ?? '',
                    'cctype' => $data['cc_type'] ?? ''
                ]);
                return "{$this->baseUrl}payment/PBB/pbb_go.php?payref={$xrandom}&{$params}";

            case 'CLK': // CIMB Clicks
                $params = http_build_query([
                    'invoice' => $data['invoice_no'] ?? '',
                    'turl' => urlencode($this->config['cimb_url'] ?? ''),
                    'response' => urlencode("{$this->baseUrl}payment/CLK/return_cimb.php"),
                    'memcode' => $data['member_code'] ?? '',
                    'amount' => $amount
                ]);
                return "{$this->baseUrl}payment/CLK/go_cimb.php?payref={$xrandom}&{$params}";

            default:
                throw new RuntimeException("Unsupported gateway code: {$gatewayCode}");
        }
    }

    public function getTransactionStatus(string $transactionId): array
    {
        return $this->gateway->getStatus($transactionId);
    }
} 