<?php
include("../../../../messages/extlanguage.php");

$debug = $_GET['debug'];
$payref = isset($_GET['xref']) ? $_GET['xref'] : "";
if (empty($payref)) {
    echo "Incomplete parameter";
    die();
}

$query = new yii\db\Query();

$data = $query->select(['param'])
        ->from('network.payment_redirection')
        ->where(['reff_code' => $payref])
        ->one();

if ($data) {
    $params = explode("&", $data['param']);

    foreach ($params as $param) {
        $dataparams = explode("=", $param);
        $myArray[$dataparams[0]] = urldecode($dataparams[1]);
    }
}
else {
    echo "Invalid parameter";
    die();
}

$amount = $myArray['amount'];
$curr_code = $myArray['_currency_code'];
$invoice = $myArray['invoice'];
$return_url = $myArray['return_url'];
$cancel_return = $myArray['cancel_return'];
//$notify_url = $myArray['_notify_url'];
$notify_url = $config->base_url . "payment/PayPal/paypalnotify.php";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <HEAD>
        <TITLE><?php echo $PAGE_TITLE; ?></TITLE>        
        <META http-equiv="Content-type" content="text/html;charset=UTF-8"/>
        <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE"/>
        <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE"/>
        <META Http-Equiv="Expires" Content="0"/>
    </HEAD>

    <body>
        <div class="container">
            <div align="center">
                <img class="img-responsive" src="/images/2017/dxn-brand.png" alt="DXN-Brand" style="width: 400px"></img>
            </div>
            <br/>
            <table bgcolor="#cccccc" align="center" border="0" cellspacing="1" cellpadding="2">
                <tr bgcolor="#e6e6e6">            
                    <td valign="middle" colspan="2"><b><?= word_lang("onlinepurchase", "PayPal Payment Information") ?> :</b></td>                    
                </tr>

                <tr bgcolor="#ffffff" >
                    <td align="left" valign="middle" width="25%">&nbsp;<?= word_lang("app", "Amount") ?> (<?= $curr_code ?>)</td>                    
                    <td align="left" valign="middle">&nbsp;<?= $amount ?></td>
                </tr>

                <tr bgcolor="#ffffff" >
                    <td align="left" valign="middle" width="25%">&nbsp;<?= word_lang("onlinepurchase", "Invoice No.") ?></td>                
                    <td align="left" valign="middle">&nbsp;<?= $invoice ?></td>
                </tr>

                <tr bgcolor="#ffffff" >
                    <td align="left" valign="middle" colspan="2">&nbsp;</td>
                </tr>
                <tr bgcolor="#ffffff" >
                    <td align="left" valign="middle" colspan="2">&nbsp;
                        <font color="orange"><b><?= word_lang("onlinepurchase", "Please do not close the browser or click Back or Stop or Refresh button.") ?></b></font>
                    </td>
                </tr>
                <tr bgcolor="#ffffff" >
                    <td align="left" valign="middle" colspan="2">&nbsp;<?= word_lang("onlinepurchase", "You will be redirected to PayPal payment page after 3 seconds") ?> ...&nbsp;<label id="cdown"></label></td>                
                </tr>
            </table>
        </div>
    </body>
</html>
<?
// Your PayPal client ID and secret
$clientID = 'AWMUDLVNmrBa-Xex4119Enva9tlaHi2WfPM1SDm_yzG0_OS8BQ2P0exWNHXeFAgAhq6FsY10trLk1z4k';
$clientSecret = 'ENoMZBW0hg7f8ppiO-xKh1Ep0u1Ns7mOoDaxXlT-8Ahx8VSrgLGD_IwjRk2eDKxyaKz3HOLq-ELsl4XM';

/*
  Sandbox. https://api-m.sandbox.paypal.com
  Live. https://api-m.paypal.com */
$apiUrl = ($_SERVER['SERVER_NAME'] !== "eworld.dxn2u.com") ? "https://api-m.sandbox.paypal.com" : "https://api-m.paypal.com";

// Initialize a cURL session
$curl = curl_init();

// Set the URL, headers, and POST data
curl_setopt_array($curl, array(
    CURLOPT_URL => "$apiUrl/v1/oauth2/token",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "grant_type=client_credentials",
    CURLOPT_HTTPHEADER => array(
        "Authorization: Basic " . base64_encode($clientID . ":" . $clientSecret),
        "Content-Type: application/x-www-form-urlencoded"
    ),
));

// Execute the session and store the result
$response = curl_exec($curl);
$err = curl_error($curl);

// Close the cURL session
curl_close($curl);

// Check for errors and output the response
if ($err) {
    echo "cURL Error #:" . $err;
}
else {
    //echo $response;
    /* {
      "scope": "https://uri.paypal.com/services/invoicing ...",
      "access_token": "A21AAFEpH4PsADK7qSS7pSRsgzfENtu-Q1ysgEDVDESseMHBYXVJYE8ovjj68elIDy8nF26AwPhfXTIeWAZHSLIsQkSYz9ifg",
      "token_type": "Bearer",
      "app_id": "APP-80W284485P519543T",
      "expires_in": 31668,
      "nonce": "2020-04-03T15:35:36ZaYZlGvEkV4yVSz8g6bAKFoGSEzuy3CQcz3ljhibkOHg"
      }'; */
    // Mengubah JSON menjadi objek PHP
    $responseObj = json_decode($response);

    $tokenType = $responseObj->token_type;
    $accessToken = $responseObj->access_token;

    $orderApiUrl = "$apiUrl/v2/checkout/orders";

    // Data to be sent in the POST request
    $postData = json_encode([
        "intent" => "CAPTURE",
        "purchase_units" => [
            [
                "reference_id" => $invoice,
                "amount" => [
                    "currency_code" => $curr_code,
                    "value" => $amount
                ]
            ]
        ],
            "payment_source" => [
                "paypal" => [
                    "experience_context" => [
            //            "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
            //            "brand_name" => "EXAMPLE INC",
            //            "locale" => "en-US",
            //            "landing_page" => "LOGIN",
            //            "shipping_preference" => "SET_PROVIDED_ADDRESS",
            //            "user_action" => "PAY_NOW",
                        "return_url" => $return_url,
                        "cancel_url" => $cancel_return
                    ]
                ]
            ]
    ]);

    // cURL headers
    $headers = [
        "Content-Type: application/json",
        //"PayPal-Request-Id: 7b92603e-77ed-4896-8e78-5dea2050476a",
        "Authorization: $tokenType $accessToken"
    ];

    // Initialize cURL session
    $ch = curl_init($orderApiUrl);

    // Set cURL options
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    // Execute cURL session and get the response
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
    }

    // Close cURL session
    curl_close($ch);

    // Output the response or error
    if (isset($error_msg)) {
        echo "Error: " . $error_msg;
    }
    else {
//        echo $response;
        /*
         * {
          "id": "6E3822276G2682731",
          "status": "CREATED",
          "links": [{
          "href": "https://api.sandbox.paypal.com/v2/checkout/orders/6E3822276G2682731",
          "rel": "self",
          "method": "GET"
          }, {
          "href": "https://www.sandbox.paypal.com/checkoutnow?token=6E3822276G2682731",
          "rel": "approve",
          "method": "GET"
          }, {
          "href": "https://api.sandbox.paypal.com/v2/checkout/orders/6E3822276G2682731",
          "rel": "update",
          "method": "PATCH"
          }, {
          "href": "https://api.sandbox.paypal.com/v2/checkout/orders/6E3822276G2682731/capture",
          "rel": "capture",
          "method": "POST"
          }]
          }
         */
    }
}

