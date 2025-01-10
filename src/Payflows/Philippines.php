<?php

namespace MultiPaymentGateway\Payflows;

class Philippines
{
    public function getConfig()
    {
        return [
            'currency' => 'PHP',
            'tax_rate' => 0.12, // 12% VAT in Philippines
            'supported_gateways' => [
                'GCSH' => [
                    'name' => 'GCash',
                    'code' => 48,
                    'min_amount' => 1,
                    'max_amount' => 100000,
                    'supported_currencies' => ['PHP']
                ]
            ]
        ];
    }

    public function validateAmount($amount)
    {
        $config = $this->getConfig();
        $limits = $config['supported_gateways']['GCSH'];
        
        return $amount >= $limits['min_amount'] && $amount <= $limits['max_amount'];
    }

    public function formatAmount($amount)
    {
        // Format amount according to PHP currency format
        return number_format($amount, 2, '.', '');
    }
} 