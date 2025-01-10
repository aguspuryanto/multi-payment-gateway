<?php
/*
 * GoCash Payment Gateway
 */
$myArray = [];
$myArray['invno'] = '1234567890';
$myArray['amount'] = 100;
$myArray['currency'] = 'PHP';

// extra
$myArray['notificationUrl'] = $baseUrl . "ajax-api/payment/GCSH/return_notify.php?invno=" . $myArray['invno'];
$myArray['redirectMerchantUrl'] = $baseUrl . "ajax-api/payment/GCSH/return_redirect.php?invno=" . $myArray['invno'];
$myArray['cancelUrl'] = $baseUrl . "ajax-api/payment/GCSH/return_cancel.php?invno=" . $myArray['invno'];

if ($_SERVER['SERVER_NAME'] == "eworld.dxn2u.com" || Yii::$app->params['systemMode'] == 'live') {
    $myArray['amount'] = $myArray['amount'] * 100; //*100
}
else {
    $myArray['amount'] = 1 * 100; //PHP 1.00
}

// Merchant Setup
// $tblMpgsSetup = Yii::$app->db->createCommand('SELECT * FROM network.mpgs_setup WHERE payflow=' . $myArray['payflow'])->queryOne();
// Yii::trace($tblMpgsSetup, 'table mpgs_setup');

$merchantId = "150570001735"; //"001710000576"; //$tblMpgsSetup['merchant_id'];
$merchantKey = "368852e5a374beeb241af528a9e584ca"; //"MPGS-43c8P0-9dsd"; //$tblMpgsSetup['api_password'];

require_once __DIR__ . "/GoCashGateway.php";
// require_once __DIR__ . "/vendor/autoload.php";
// Usage
try {
    $config = [
        'mch_id' => $merchantId,
        'mch_key' => $merchantKey,
        'notify_url' => $myArray['notificationUrl'],
        'callback_url' => $myArray['redirectMerchantUrl'],
    ];

    // $SwiftPass = new \Barbery\SwiftPass($config);
    $SwiftPass = new GoCashGateway($config);

    // Cashier counter pre-order interface
    $data = [
        'service' => 'unified.checkout.prepay',
        'sign_type' => 'MD5',
        'body' => 'Eworld Payment',
        'out_trade_no' => $myArray['invno'],
        'nonce_str' => $myArray['invno'],
        'total_fee' => $myArray['amount'],
        'is_raw' => 1,
        'time_start' => date('YmdHis'),
        'time_expire' => date('YmdHis', strtotime('+30minute')),
    ];
    // Yii::trace($data, '_data');

    $strXml = $SwiftPass->pay($data);
    Yii::trace($strXml, '_strXml');
    if ($strXml) {
        // Mengubah XML menjadi array
        $xmlObject = simplexml_load_string($strXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $arrayData = json_decode(json_encode($xmlObject), true);

        // Prepare the data for insertion
        // result_code 0 : success. Others value : fail.
        $insData = [
            'invno' => $myArray['invno'],
            'amount' => (int) $myArray['amount'],
            'result_code' => $arrayData['result_code'],
            'response_payment' => json_encode($arrayData),
        ];

        // Prepare the SQL statement
        $sql = "INSERT INTO network.gcash_request (" . implode(', ', array_keys($insData)) . ") "
                . "SELECT '" . implode("', '", array_values($insData)) . "' "
                . "WHERE NOT EXISTS (SELECT * FROM network.gcash_request WHERE invno='" . $insData['invno'] . "')";

        // Execute the command
        $db->createCommand($sql)->execute();
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="<?= $session['default_lang'] ?>">
    <head>
        <title>MPGS Payment Gateway</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="cache-control" content="no-cache"/>
        <meta http-equiv="pragma" content="no-cache"/>
        <meta http-equiv="expires" content="0"/>
        <script type="text/javascript">
            // Checkout.showPaymentPage();
            const result_code = "<?= $arrayData['result_code']; ?>";
            const err_msg = "<?= $arrayData['err_msg']; ?>";

            var intCountDown = 3;
            function countDown() {
                document.getElementById('cdown').innerHTML = intCountDown;

                if (intCountDown <= 0) {
                    // Checkout.showPaymentPage();
                    console.log("Redirecting to Payment Gateway...");
                    if (result_code == '1') {
                        window.location.href = window.history.back();
                    } else {
                        window.location.href = "<?= $arrayData['code_url']; ?>";
                    }
                    return;
                }
                intCountDown--;

                setTimeout("countDown()", 1000);
            }

            // countDown();
        </script>
    </head>
    <body onload="<?= ($debug == 0) ? "countDown();" : ""; ?>">
        <div align="center">
            <img class="img-responsive" src="/images/2017/dxn-brand.png" alt="DXN-Brand" style="width: 400px"></img>
        </div>
        <br/>          
        <table bgcolor="#cccccc" align="center" border="0" cellspacing="1" cellpadding="2">
            <tr bgcolor="#e6e6e6">            
                <td valign="middle" colspan="2"><b><?= Yii::t('epoint', 'GCash Payment Information :') ?></b></td>                
            </tr>
            <tr bgcolor="#ffffff" >
                <td align="left" valign="middle" width="25%">&nbsp;<?= Yii::t('epoint', 'Amount') ?> (<?= $myArray['currency'] ?>)</td>
                <td align="left" valign="middle">&nbsp;<?= number_format($myArray['amount'], 2) ?></td>
            </tr>
            <tr bgcolor="#ffffff" >
                <td align="left" valign="middle" width="25%">&nbsp;<?= Yii::t('epoint', 'Invoice No') ?></td>
                <td align="left" valign="middle">&nbsp;<?= $myArray['invno'] ?></td>
            </tr>
            <?php
            if ($arrayData['result_code'] == '1') {
                echo '<tr bgcolor="#ffffff" >
                    <td colspan="2" align="left" valign="middle">&nbsp;' . $arrayData['err_msg'] . '</td>
                </tr>';
            }
            ?>
            <tr bgcolor="#ffffff" >
                <td align="left" valign="middle" colspan="2">&nbsp;<font color="orange"><b><?= Yii::t('epoint', 'Please do not close the browser or click Back or Stop or Refresh button.') ?></b></font></td>
            </tr>
            <tr bgcolor="#ffffff" >
                <td align="left" valign="middle" colspan="2">&nbsp;<?= Yii::t('epoint', 'You will be redirected to MPGS payment page...') ?>&nbsp;<label id="cdown"></label></td>
            </tr>
        </table>
    </body>
</html>