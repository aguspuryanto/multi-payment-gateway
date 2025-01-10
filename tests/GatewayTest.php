<?php

namespace MultiPaymentGateway\Tests;

use MultiPaymentGateway\PaymentManager;
use PHPUnit\Framework\TestCase;

class GatewayTest extends TestCase
{
    private array $config;
    private string $baseUrl;

    protected function setUp(): void
    {
        $this->baseUrl = 'https://ew-dev.dxn2u.com/ajax-api/';
        $this->config = [
            'cimb_url' => 'https://uat.cimbclicks.com.my/TIBSEPWeb/ePayment.do'
        ];
    }

    public function testProcessPayment()
    {
        $paymentManager = new PaymentManager('CCD', 'MY', $this->baseUrl, $this->config);
        $result = $paymentManager->process(100.00, 'MYR', [
            'gateway_code' => 'CCD',
            'invoice_no' => 'INV-001',
            'member_code' => 'MEM001',
            'country_id' => 'MY',
            'cc_type' => 'VISA'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(0.06, $result['tax_rate']);
        $this->assertEquals(6.00, $result['tax_amount']);
        $this->assertStringContainsString('payment/PBB/pbb_go.php', $result['payment_url']);
        $this->assertStringContainsString('payref=PAY-', $result['payment_url']);
    }

    public function testGetTransactionStatus()
    {
        $paymentManager = new PaymentManager('CCD', 'MY', $this->baseUrl, $this->config);
        $result = $paymentManager->process(100.00, 'MYR', [
            'gateway_code' => 'CCD',
            'invoice_no' => 'INV-001'
        ]);
        $status = $paymentManager->getTransactionStatus($result['transaction_id']);

        $this->assertEquals('completed', $status['status']);
    }

    public function testInvalidGatewayCode()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported gateway code: INVALID');
        
        $paymentManager = new PaymentManager('CCD', 'MY', $this->baseUrl, $this->config);
        $paymentManager->process(100.00, 'MYR', [
            'gateway_code' => 'INVALID'
        ]);
    }
} 