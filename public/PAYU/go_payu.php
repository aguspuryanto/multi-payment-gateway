<?php
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include_once __DIR__ . "/../../../../messages/extlanguage.php";

use app\models\paymentRedirection;
use app\models\pyuSetup;
use Yii;

$referrer = strpos($_SERVER['HTTP_REFERER'], "dxn2u.com");
$session = Yii::$app->session;
// session_start();

$baseUrl = "https://" . $_SERVER['SERVER_NAME'] . "/";

// include_once __DIR__ . '/common.php';
$config = simplexml_load_file(__DIR__ . "/../../config/setup.xml");
//$dbconn = pg_connect("host=$config->db_host port=$config->db_port dbname=$config->db_name user=$config->db_user password=$config->db_pass");
$db = Yii::$app->db;
//if (!$dbconn) {
//    print "Failed to connect";
//exit;
//}

include(__DIR__ . "/../cekValidationToken.php");
if ($referrer === false && tokenValid() != 1) {
    if (Yii::$app->userstaff->isGuest && Yii::$app->user->isGuest || tokenValid() != 1) {
        //echo "Access Denied, accessed from " . $_SERVER["REMOTE_ADDR"] . ", referrer " . $_SERVER["HTTP_REFERER"];
        //die();
    }
}

$debug = 0;
if (isset($_GET['debug'])) {
    $debug = $_GET['debug'];
}

$xref = $_GET['ref'];
if ($xref == "") {
    echo "Invalid Parameter";
    die();
}

$payRedirect = paymentRedirection::findOne(['reff_code' => $xref]);
if (is_null($payRedirect)) {
    echo "Payment redirection Not Found";
    die();
}

$params = explode("&", $payRedirect->param);

foreach ($params as $param) {
    $dataparams = explode("=", $param);
    $myArray[$dataparams[0]] = $dataparams[1];
}

/* data custom */
$myArray['successUrl'] = "https://" .$_SERVER['HTTP_HOST']. "/ajax-api/payment/PAYU/payu_response.php";
$myArray['failUrl'] = "https://" .$_SERVER['HTTP_HOST']. "/ajax-api/payment/PAYU/return_fail.php";
$myArray['cancelUrl'] = ""; //$config->base_url . "payment/PAYU/return_cancel.php";
$myArray['Pg'] = "CC"; //CC for Credit Card, NB for Net Banking, DC for Debit Card, CASH, EMI
$buyerName = explode(" ", $myArray['buyerName']);
$myArray['firstname'] = $buyerName[0];
$myArray['Lastname'] = $buyerName[1];

// $oldCart = \app\models\tempDetail::find()->where(['refnoinv' => $myArray['invno']])->orderBy('lineno')->all();
// echo json_encode($oldCart) . "<br>";

$myArray['udf1'] = $payRedirect->memcode;
$myArray['udf2'] = $myArray['branch'];
$myArray['udf3'] = "";
$myArray['udf4'] = "";
$myArray['udf5'] = "PayUBiz_PHP7_Kit";
$myArray['productinfo'] = "DXN Product(s)";
// if(empty($myArray['buyerPhone'])) $myArray['buyerPhone'] = "55553456789";

$brcode = $myArray['branch'];
// echo json_encode($myArray) . "<br>";
// $companyx = \app\models\Company::findOne(['bc_id' => $brcode, 'for_ctry' => 'DI']);

$pyuSetup = pyuSetup::findOne(['branch' => $brcode]);
$merchantId = $pyuSetup->mid;
$merchantKey = $pyuSetup->key;
$merchantSalt = $pyuSetup->salt;

/*
  Note : It is recommended to fetch all the parameters from your Database rather than posting static values or entering them on the UI.

  POST REQUEST to be posted to below mentioned PayU URLs:

  For PayU Test Server:
  POST URL: https://test.payu.in/_payment

  For PayU Production (LIVE) Server:
  POST URL: https://secure.payu.in/_payment
 */

//Unique merchant key provided by PayU along with salt. Salt is used for Hash signature 
//calculation within application and must not be posted or transfered over internet. //-->
// $key="gtKFFx";
// $salt="wia56q6O";

$action = (strpos($_SERVER['SERVER_NAME'], 'eworld.dxn2u.com') !== false) ? 'https://secure.payu.in/_payment' : 'https://test.payu.in/_payment';

$html = '';

if (strcasecmp($_SERVER['REQUEST_METHOD'], 'GET') == 0) {
    /* Request Hash
      ----------------
      For hash calculation, you need to generate a string using certain parameters
      and apply the sha512 algorithm on this string. Please note that you have to
      use pipe (|) character as delimeter.
      The parameter order is mentioned below:

      sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)

      Description of each parameter available on html page as well as in PDF.

      Case 1: If all the udf parameters (udf1-udf5) are posted by the merchant. Then,
      hash=sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)

      Case 2: If only some of the udf parameters are posted and others are not. For example, if udf2 and udf4 are posted and udf1, udf3, udf5 are not. Then,
      hash=sha512(key|txnid|amount|productinfo|firstname|email||udf2||udf4|||||||SALT)

      Case 3: If NONE of the udf parameters (udf1-udf5) are posted. Then,
      hash=sha512(key|txnid|amount|productinfo|firstname|email|||||||||||SALT)

      In present kit and available PayU plugins UDF5 is used. So the order is -
      hash=sha512(key|txnid|amount|productinfo|firstname|email|||||udf5||||||SALT)

     */
    //generate hash with mandatory parameters and udf5
    $hash = hash('sha512', $merchantKey . '|' . $myArray['invno'] . '|' . $myArray['amount'] . '|' . $myArray['productinfo'] . '|' . $myArray['firstname'] . '|' . $myArray['buyerEmail'] . '|' . $myArray['udf1'] . '|' . $myArray['udf2'] . '|' . $myArray['udf3'] . '|' . $myArray['udf4'] . '|' . $myArray['udf5'] . '||||||' . $merchantSalt);

    $_SESSION['salt'] = $merchantSalt; //save salt in session to use during Hash validation in response
    // pyu_request
    $query = "SELECT * FROM network.pyu_request WHERE pyu_key='" . $merchantKey . "' AND pyu_txid='" . $myArray['invno'] . "'";
    $res = $db->createCommand($query)->queryOne();
    //$res = pg_exec($dbconn, $query);
    if (!$res) {
        $timestamp = date('Y-m-d G:i:s');
        $querr = "INSERT INTO network.pyu_request (pyu_key, pyu_txid, pyu_amount, pyu_productinfo, pyu_firstname, pyu_email, pyu_udf1, pyu_udf2, pyu_udf5, pyu_status, pyu_hash, pyu_created) "
                . "VALUES ('" . $merchantKey . "', '" . $myArray['invno'] . "', '" . $myArray['amount'] . "', '" . $myArray['productinfo'] . "', '" . $myArray['firstname'] . "', '" . $myArray['buyerEmail'] . "', '" . $myArray['udf1'] . "', '" . $myArray['udf2'] . "', '" . $merchantSalt . "', 'pyu_request', '" . $hash . "', '" . $timestamp . "')";
        $result = pg_query($dbconn, $querr);
        $db->createCommand()->insert('network.pyu_request', [
            'pyu_key' => $merchantKey,
            'pyu_txid' => $myArray['invno'],
            'pyu_amount' => $myArray['amount'],
            'pyu_productinfo' => $myArray['productinfo'],
            'pyu_firstname' => $myArray['firstname'],
            'pyu_email' => $myArray['buyerEmail'],
            'pyu_udf1' => $myArray['udf1'],
            'pyu_udf2' => $myArray['udf2'],
            'pyu_udf5' => $merchantSalt,
            'pyu_status' => 'pyu_request',
            'pyu_hash' => $hash,
            'pyu_created' => $timestamp
        ])->execute();
    }
    // 

    $myArray['buyerCountry'] = "India";

    $html = '<form action="' . $action . '" id="payment_form_submit" method="post">
            <input type="hidden" id="udf1" name="udf1" value="' . $myArray['udf1'] . '" />
            <input type="hidden" id="udf2" name="udf2" value="' . $myArray['udf2'] . '" />
            <input type="hidden" id="udf3" name="udf3" value="' . $myArray['udf3'] . '" />
            <input type="hidden" id="udf4" name="udf4" value="' . $myArray['udf4'] . '" />
            <input type="hidden" id="udf5" name="udf5" value="' . $myArray['udf5'] . '" />
            <input type="hidden" id="surl" name="surl" value="' . $myArray['successUrl'] . '" />
            <input type="hidden" id="furl" name="furl" value="' . $myArray['failUrl'] . '" />
            <input type="hidden" id="key" name="key" value="' . $merchantKey . '" />
            <input type="hidden" id="txnid" name="txnid" value="' . $myArray['invno'] . '" />
            <input type="hidden" id="amount" name="amount" value="' . $myArray['amount'] . '" />
            <input type="hidden" id="productinfo" name="productinfo" value="' . $myArray['productinfo'] . '" />
            <input type="hidden" id="firstname" name="firstname" value="' . $myArray['firstname'] . '" />
            <input type="hidden" id="Lastname" name="Lastname" value="' . $myArray['Lastname'] . '" />
            <input type="hidden" id="Zipcode" name="Zipcode" value="' . $myArray['buyerPostalCode'] . '" />
            <input type="hidden" id="email" name="email" value="' . $myArray['buyerEmail'] . '" />
            <input type="hidden" id="phone" name="phone" value="' . $myArray['buyerPhone'] . '" />
            <input type="hidden" id="address1" name="address1" value="' . $myArray['buyerAddress'] . '" />
            <input type="hidden" id="address2" name="address2" value="' . (isset($myArray['buyerAddress2']) ? $myArray['buyerAddress2'] : '') . '" />
            <input type="hidden" id="city" name="city" value="' . $myArray['buyerCity'] . '" />
            <input type="hidden" id="state" name="state" value="' . $myArray['buyerState'] . '" />
            <input type="hidden" id="country" name="country" value="' . $myArray['buyerCountry'] . '" />
            <input type="hidden" id="Pg" name="Pg" value="' . $myArray['Pg'] . '" />
            <input type="hidden" id="hash" name="hash" value="' . $hash . '" />';

    if ($debug == 1) {
        $html .= '<input type="button" id="btnsubmit" name="btnsubmit" value="Pay" onclick="frmsubmit(); return true;" />';
    }

    $html .= '</form>';

    // sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)
    $hashv2 = hash('sha512', $merchantKey . '|' . $myArray['invno'] . '|' . $myArray['amount'] . '|' . $myArray['productinfo'] . '|' . $myArray['firstname'] . '|' . $myArray['buyerEmail'] . '|' . $myArray['udf1'] . '|' . $myArray['udf2'] . '|' . $myArray['udf3'] . '|' . $myArray['udf4'] . '|' . $myArray['udf5'] . '||||||' . $merchantSalt);

    $htmlv2 = '<form action="' . $action . '" id="payment_form_submit" method="post">
        <input type="hidden" name="key" value="' . $merchantKey . '" />
        <input type="hidden" name="txnid" value="' . $myArray['invno'] . '" />
        <input type="hidden" name="productinfo" value="' . $myArray['productinfo'] . '" />
        <input type="hidden" name="amount" value="' . $myArray['amount'] . '" />
        <input type="hidden" name="email" value="' . $myArray['buyerEmail'] . '" />
        <input type="hidden" name="firstname" value="' . $myArray['firstname'] . '" />
        <input type="hidden" name="lastname" value="' . $myArray['Lastname'] . '" />
        <input type="hidden" name="pg" value="' . $myArray['Pg'] . '" />
        <input type="hidden" name="bankcode" value="MAST" />
        <input type="hidden" name="surl" value="' . $myArray['successUrl'] . '" />
        <input type="hidden" name="furl" value="' . $myArray['failUrl'] . '" />
        <input type="hidden" name="phone" value="' . $myArray['buyerPhone'] . '" />
        <input type="hidden" name="hash" value="' . $hashv2 . '" />';

    if ($debug == 1) {
        $htmlv2 .= '<input type="button" id="btnsubmit" name="btnsubmit" value="submit" onclick="frmsubmit(); return true;" />';
    }

    $htmlv2 .= '</form>';
}

//This function is for dynamically generating callback url to be postd to payment gateway. Payment response will be
//posted back to this url. 
function getCallbackUrl() {
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $uri = str_replace('/index.php', '/', $_SERVER['REQUEST_URI']);
    return $protocol . $_SERVER['HTTP_HOST'] . $uri . 'response.php';
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>DXN e-World</title>
        <link rel="shortcut icon" type="image/gif" href="/images/logo.gif" />
        <META http-equiv="Content-type" content="text/html;charset=UTF-8"/>
        <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE"/>
        <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE"/>
        <META Http-Equiv="Expires" Content="0"/>
        <META NAME="COPYRIGHT" CONTENT="Copyright 2005-2006 DXN + SURYASOFT DOT NET"/>
        <META NAME="DESCRIPTION" CONTENT="Networkers - DXN Information System 2005-2006"/>

        <script type="text/javascript">
            var intCountDown = 2;

            function countDown() {
                document.getElementById('cdown').innerHTML = intCountDown;

                if (intCountDown <= 0) {
                    // document.payment_form_submit.submit();
                    document.getElementById("payment_form_submit").submit();
                    return;
                }
                intCountDown--;

                setTimeout("countDown()", 1000);
            }

            function frmsubmit() {
                document.getElementById("payment_form_submit").submit();
                return true;
            }
        </script>
    </head>
    <body onload="<? echo ($debug == 1) ? "" : "countDown();"; ?>">
        <div align="center">
            <img class="img-responsive" src="/images/2017/dxn-brand.png" alt="DXN-Brand" style="width: 400px"></img>
        </div>
        <br/>          
        <table bgcolor="#cccccc" align="center" border="0" cellspacing="1" cellpadding="2">
            <tr bgcolor="#e6e6e6">            
                <td valign="middle" colspan="2"><b><?= Yii::t('onlinepurchase', 'PayU Payment Information :') ?></b></td>                
            </tr>
            <tr bgcolor="#ffffff" >
                <td align="left" valign="middle" width="25%">&nbsp;<?= Yii::t('onlinepurchase', 'Amount') ?> (INR)</td>
                <td align="left" valign="middle">&nbsp;<?= number_format($myArray['amount'], 2, '.', ',') ?></td>
            </tr>
            <tr bgcolor="#ffffff" >
                <td align="left" valign="middle" width="25%">&nbsp;<?= Yii::t('onlinepurchase', 'Invoice No') ?></td>
                <td align="left" valign="middle">&nbsp;<?= $myArray['invno'] ?></td>
            </tr>
            <tr bgcolor="#ffffff" >
                <td align="left" valign="middle" colspan="2">&nbsp;<font color="orange"><b><?= Yii::t('onlinepurchase', 'Please do not close the browser or click Back or Stop or Refresh button.') ?></b></font></td>
            </tr>
            <tr bgcolor="#ffffff" >
                <td align="left" valign="middle" colspan="2">&nbsp;<?= Yii::t('onlinepurchase', 'You will be redirected to PayU payment page...') ?>&nbsp;<label id="cdown"></label></td>
            </tr>
        </table>
        <?php if ($html) echo $html; //submit request to PayUBiz   ?>
    </body>
</html>
