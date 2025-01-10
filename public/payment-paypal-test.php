<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MultiPaymentGateway\PaymentManager;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$baseUrl = 'https://ew-dev.dxn2u.com/ajax-api/';
$config = [
    'client_id' => 'AWMUDLVNmrBa-Xex4119Enva9tlaHi2WfPM1SDm_yzG0_OS8BQ2P0exWNHXeFAgAhq6FsY10trLk1z4k',
    'client_secret' => 'ENoMZBW0hg7f8ppiO-xKh1Ep0u1Ns7mOoDaxXlT-8Ahx8VSrgLGD_IwjRk2eDKxyaKz3HOLq-ELsl4XM'
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>PayPal Payment Test</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        td, th { padding: 8px; border: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .error { color: red; }
        .success { color: green; }
        .btn { padding: 10px 15px; background: #0070ba; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #003087; }
    </style>
</head>
<body>
    <div class="container">
        <div align="center">
            <img src="/images/2017/dxn-brand.png" alt="DXN-Brand" style="width: 400px">
        </div>
        <br/>

        <?php
        try {
            // Initialize PaymentManager
            $paymentManager = new PaymentManager('PYPL', 'US', $baseUrl, $config);

            // Process payment
            $result = $paymentManager->process(100.00, 'USD', [
                'gateway_code' => 'PYPL',
                'invoice_no' => 'INV-PP-' . time(),
                'buyer_name' => 'John Doe',
                'buyer_email' => 'john@example.com',
                'buyer_phone' => '+1234567890',
                'buyer_address' => '123 Test Street',
                'buyer_city' => 'Test City',
                'buyer_state' => 'TS',
                'buyer_country' => 'US',
                'buyer_postal_code' => '12345'
            ]);

            // Display payment information
            ?>
            <table>
                <tr>
                    <th colspan="2">PayPal Payment Information</th>
                </tr>
                <tr>
                    <td width="30%">Amount (USD)</td>
                    <td>100.00</td>
                </tr>
                <tr>
                    <td>Invoice No.</td>
                    <td><?php echo $result['params']['invoice']; ?></td>
                </tr>
                <tr>
                    <td>Transaction ID</td>
                    <td><?php echo $result['transaction_id']; ?></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div style="color: orange;">
                            <strong>Please do not close the browser or click Back or Stop or Refresh button.</strong>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        You will be redirected to PayPal payment page after <span id="countdown">3</span> seconds...
                    </td>
                </tr>
            </table>

            <?php if ($result['success']): ?>
                <div id="paymentButtons" style="text-align: center; display: none;">
                    <button class="btn" onclick="window.location.href='<?php echo $result['payment_url']; ?>'">
                        Proceed to PayPal
                    </button>
                </div>

                <script>
                    // Countdown timer
                    let timeLeft = 3;
                    const countdownElement = document.getElementById('countdown');
                    const paymentButtons = document.getElementById('paymentButtons');
                    
                    const countdownTimer = setInterval(function() {
                        timeLeft--;
                        countdownElement.textContent = timeLeft;
                        
                        if (timeLeft <= 0) {
                            clearInterval(countdownTimer);
                            window.location.href = '<?php echo $result['payment_url']; ?>';
                        }
                    }, 1000);

                    // Show payment buttons immediately
                    paymentButtons.style.display = 'block';

                    // Function to check payment status
                    function checkStatus(transactionId) {
                        fetch('payment-paypal-status.php?transaction_id=' + transactionId)
                            .then(response => response.json())
                            .then(data => {
                                alert('Transaction Status: ' + data.status);
                            })
                            .catch(error => {
                                alert('Error checking status: ' + error);
                            });
                    }
                </script>
            <?php endif; ?>

        <?php
        } catch (Exception $e) {
            ?>
            <div class="error">
                <h2>Error:</h2>
                <p><?php echo $e->getMessage(); ?></p>
                <p><a href="javascript:history.back()">Go Back</a></p>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html> 