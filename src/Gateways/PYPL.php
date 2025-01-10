<?php

namespace MultiPaymentGateway\Gateways;

class PYPL
{
    protected $baseUrl;
    protected $config;
    protected $apiUrl;
    protected $clientId;
    protected $clientSecret;

    public function __construct($baseUrl, $config = [])
    {
        $this->baseUrl = $baseUrl;
        $this->config = $config;
        
        // Set PayPal environment
        $isProduction = ($_SERVER['SERVER_NAME'] === "eworld.dxn2u.com");
        $this->apiUrl = $isProduction ? "https://api-m.paypal.com" : "https://api-m.sandbox.paypal.com";
        
        // Set credentials
        $this->clientId = $config['client_id'] ?? 'AWMUDLVNmrBa-Xex4119Enva9tlaHi2WfPM1SDm_yzG0_OS8BQ2P0exWNHXeFAgAhq6FsY10trLk1z4k';
        $this->clientSecret = $config['client_secret'] ?? 'ENoMZBW0hg7f8ppiO-xKh1Ep0u1Ns7mOoDaxXlT-8Ahx8VSrgLGD_IwjRk2eDKxyaKz3HOLq-ELsl4XM';
    }

    public function processPayment($amount, $currency, $params = [])
    {
        // Get access token
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            throw new \Exception("Failed to get PayPal access token");
        }

        // Format amount
        $amount = number_format($amount, 2, '.', '');

        // Store parameters for redirection
        $redirectParams = [
            'amount' => $amount,
            '_currency_code' => $currency,
            'invoice' => $params['invoice_no'] ?? '',
            'return_url' => $this->baseUrl . "payment/PYPL/return_success.php",
            'cancel_return' => $this->baseUrl . "payment/PYPL/cancel.php",
        ];

        // Create PayPal order
        $orderData = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "reference_id" => $params['invoice_no'],
                    "amount" => [
                        "currency_code" => $currency,
                        "value" => $amount
                    ]
                ]
            ],
            "payment_source" => [
                "paypal" => [
                    "experience_context" => [
                        "return_url" => $redirectParams['return_url'],
                        "cancel_url" => $redirectParams['cancel_return']
                    ]
                ]
            ]
        ];

        $response = $this->createPayPalOrder($accessToken, $orderData);
        $responseData = json_decode($response, true);

        if (!isset($responseData['links'])) {
            throw new \Exception("Invalid PayPal response");
        }

        // Find the approval URL
        $approvalUrl = '';
        foreach ($responseData['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approvalUrl = $link['href'];
                break;
            }
        }

        return [
            'success' => true,
            'payment_url' => $approvalUrl,
            'transaction_id' => $responseData['id'],
            'params' => $redirectParams
        ];
    }

    protected function getAccessToken()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "{$this->apiUrl}/v1/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => "grant_type=client_credentials",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . base64_encode($this->clientId . ":" . $this->clientSecret),
                "Content-Type: application/x-www-form-urlencoded"
            ],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }

        return null;
    }

    protected function createPayPalOrder($accessToken, $orderData)
    {
        $ch = curl_init("{$this->apiUrl}/v2/checkout/orders");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($orderData),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer {$accessToken}"
            ],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function getTransactionStatus($transactionId)
    {
        $accessToken = $this->getAccessToken();
        
        $ch = curl_init("{$this->apiUrl}/v2/checkout/orders/{$transactionId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer {$accessToken}"
            ],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        return [
            'status' => strtolower($data['status'] ?? 'pending'),
            'transaction_id' => $transactionId,
            'gateway' => 'PYPL'
        ];
    }
} 