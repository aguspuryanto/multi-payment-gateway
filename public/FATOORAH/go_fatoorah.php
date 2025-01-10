<?php

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include ("../../../../messages/extlanguage.php");
include 'src/MyFatoorah.php';

use app\models\tempHeader;
use app\models\Company;
use app\components\CurlTool;

$dataSetup = simplexml_load_file(__DIR__ . "/../../config/setup.xml");

$baseURL = str_replace("/ajax-api", "", $dataSetup->base_url);

$xref = isset($_GET['ref']) ? $_GET['ref'] : '';
if ($xref == "") {
    echo "Invalid Parameter";
    die();
}

$payRedirect = app\models\paymentRedirection::findOne(['reff_code' => $xref]);
if (is_null($payRedirect)) {
    echo "Payment redirection Not Found";
    die();
}

$params = explode("&", $payRedirect->param);
foreach ($params as $param) {
    $dataparams = explode("=", $param);
    $myArray[$dataparams[0]] = $dataparams[1];
}

$inv_no = $myArray['invno'];
$amount = $myArray['amount'];

// $merchantCode = "[Your merchant code here]";
// $username = "[Your merchant username here]";
// $password = "[Your merchant password here]";
// $my = MyFatoorah::live($merchantCode, $username, $password);

if ($_SERVER['SERVER_NAME'] == "eworld.dxn2u.com" || Yii::$app->params['systemMode'] == 'live') {
    $my = MyFatoorah::live();
} else {
    $my = new MyFatoorah();
    $my->merchantCode = "999999";
    $my->merchantUsername = "demoApiuser@myfatoorah.com";
    $my->merchantPassword = "Mf@12345678";
    $my->test();
}
// echo var_dump($my); die();

$my->setPaymentMode(MyFatoorah::GATEWAY_ALL)
        ->setReturnUrl($baseURL . "/payment/MyFatoorah/return_success.php")
        ->setErrorReturnUrl($baseURL . "/payment/MyFatoorah/return_fail.php")
        ->setCustomer("Khalid", "customer@email.com", "97738271") //$name, $email, $phone
        ->setReferenceId($inv_no) //Pass unique order number or leave empty to use time()
        ->addProduct("DXN Product(s)", $amount, 1)
        ->getPaymentLinkAndReference();

$paymentUrl = $my->paymentUrl;
$myfatoorahRefId = $my->paymentRef; //good idea to store this for later status checks

echo "paymentUrl:" . $paymentUrl . "<br>";
echo "paymentRef:" . $myfatoorahRefId . "<br>";
// Redirect to payment url
if ($paymentUrl) {
    header("Location: $paymentUrl");
    die();
}
?>