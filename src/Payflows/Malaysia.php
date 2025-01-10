<?php

namespace MultiPaymentGateway\Payflows;

class Malaysia implements PayflowInterface
{
    public function getConfig(): array
    {
        return [
            'currency' => 'MYR',
            'tax_rate' => 0.06,
            'supported_gateways' => ['CCD', 'BTR', 'PPL']
        ];
    }

    public function validateCurrency(string $currency): bool
    {
        return $currency === 'MYR';
    }
} 