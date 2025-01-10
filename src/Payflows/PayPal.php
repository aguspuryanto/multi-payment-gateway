<?php

namespace MultiPaymentGateway\Payflows;

class PayPal
{
    public function getConfig()
    {
        return [
            'supported_currencies' => [
                'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'SGD', 
                'MYR', 'PHP', 'THB', 'IDR', 'INR'
            ],
            'supported_gateways' => [
                'PYPL' => [
                    'name' => 'PayPal',
                    'code' => 1,
                    'min_amount' => 1,
                    'max_amount' => 10000,
                    'client_id' => 'AWMUDLVNmrBa-Xex4119Enva9tlaHi2WfPM1SDm_yzG0_OS8BQ2P0exWNHXeFAgAhq6FsY10trLk1z4k',
                    'client_secret' => 'ENoMZBW0hg7f8ppiO-xKh1Ep0u1Ns7mOoDaxXlT-8Ahx8VSrgLGD_IwjRk2eDKxyaKz3HOLq-ELsl4XM'
                ]
            ]
        ];
    }

    public function validateAmount($amount)
    {
        $config = $this->getConfig();
        $limits = $config['supported_gateways']['PYPL'];
        
        return $amount >= $limits['min_amount'] && $amount <= $limits['max_amount'];
    }

    public function validateCurrency($currency)
    {
        $config = $this->getConfig();
        return in_array($currency, $config['supported_currencies']);
    }

    public function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }
} 