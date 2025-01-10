<?php
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include("../../../../messages/extlanguage.php");

$referrer = strpos($_SERVER['HTTP_REFERER'], "dxn2u.com");

include("../cekValidationToken.php");
if ($referrer === false && tokenValid() != 1) {
    if (Yii::$app->userstaff->isGuest && Yii::$app->user->isGuest || tokenValid() != 1) {
        echo "Access Denied, accessed from " . $_SERVER["REMOTE_ADDR"] . ", referrer " . $_SERVER["HTTP_REFERER"];
        // die();
    }
}

include_once 'common.php';
include __DIR__ . '/apiRedsys.php';

$config = simplexml_load_file(__DIR__ . "/../../config/setup.xml");
$dbconn = pg_connect("host=$config->db_host port=$config->db_port dbname=$config->db_name user=$config->db_user password=$config->db_pass");
if (!$dbconn) {
    print "Failed to connect";
    exit;
}

$errorMsg = "";

if (isset($_GET)) {
    $reff = $_GET["reff"];
} else {
    $errorMsg = 'The session has expired, please return to your merchant website and try again.';
}

if ($reff == "") {
    $errorMsg = 'The session has expired, please return to your merchant website and try again.';
}

if ($errorMsg == "") {
    $ksql = "SELECT * FROM network.payment_redirection WHERE reff_code='$reff';";
    $kres = pg_query($dbconn, $ksql);

    if (pg_num_rows($kres) > 0) {
        $data = pg_fetch_object($kres);
        $params = explode("&", $data->param);
        foreach ($params as $param) {
            $dataparams = explode("=", $param);
            $key = "form_" . $dataparams[0];
            $$key = urldecode($dataparams[1]);
        }

        $session = Yii::$app->session;
        $session['rds_refno'] = $form_reff;
        if (empty($session['rds_refno'])) {
            $form_reff = $data->trcd;
        }
        if ($debug == 1) {
            echo $form_reff;
        }

        $miObj = new RedsysAPI;
        $miObj->setParameter("DS_MERCHANT_AMOUNT", str_replace('.', '', $form_amt));
        $miObj->setParameter("DS_MERCHANT_ORDER", "$form_reff");
        //$miObj->setParameter("DS_MERCHANT_ORDER", preg_replace('/\D/', '', $form_ref));
        $miObj->setParameter("DS_MERCHANT_MERCHANTCODE", "$form_merchantcode");
        $miObj->setParameter("DS_MERCHANT_CURRENCY", "$form_currency");
        $miObj->setParameter("DS_MERCHANT_TRANSACTIONTYPE", "$form_transactiontype");
        $miObj->setParameter("DS_MERCHANT_TERMINAL", "$form_terminal");
        $miObj->setParameter("DS_MERCHANT_MERCHANTURL", "$form_merchanturl");
        $miObj->setParameter("DS_MERCHANT_URLOK", "$form_urlok");
        $miObj->setParameter("DS_MERCHANT_URLKO", "$form_urlko");
        $miObj->setParameter("DS_MERCHANT_CONSUMERLANGUAGE", "$form_customerlanguage");

        $Ds_SignatureVersion = $config->rds_signatureversion;
        $Ds_MerchantParameters = $miObj->createMerchantParameters();
        $Ds_Signature = $miObj->createMerchantSignature($config->rds_storekey);

        if (!pg_connection_busy($dbconn)) {
            $query = "SELECT * FROM network.rds_request WHERE rds_order='" . $form_reff . "'";
            $res = pg_exec($dbconn, $query);
            if (pg_num_rows($res) == 0) {
                $timestamp = date('Y-m-d G:i:s');
                $querr = "INSERT INTO network.rds_request (rds_order, rds_memcode, rds_amount, rds_merchant, rds_transtype, rds_terminal, rds_currency, rds_successurl, rds_errorurl, rds_keystore, rds_sigvers, rds_created) "
                        . "VALUES ('$form_reff', '$form_code', '" . $form_amt . "', '" . $form_merchantcode . "', '" . $form_transactiontype . "', '" . $form_terminal . "', '" . $form_currency . "', '$form_urlok', '$form_urlko', '" . $config->rds_storekey . "', '" . $config->rds_signatureversion . "', '$timestamp')";
                $result = pg_query($dbconn, $querr);
            }
        }
    } else {
        $errorMsg = 'Parameter not found, please return and try again.';
    }
}
pg_close($dbconn);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <link rel="shortcut icon" type="image/gif" href="/images/logo.gif" />
        <META http-equiv="Content-type" content="text/html;charset=UTF-8"/>
        <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE"/>
        <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE"/>
        <META Http-Equiv="Expires" Content="0"/>
        <META NAME="COPYRIGHT" CONTENT="Copyright 2005-2006 DXN + SURYASOFT DOT NET"/>
        <META NAME="DESCRIPTION" CONTENT="Networkers - DXN Information System 2005-2006"/>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://static.eworldglobal.com/htdocs/bootstrap/3.4.1/css/bootstrap.min.css" />

        <style type="text/css">
            /* Move down content because we have a fixed navbar that is 50px tall */
            body {
                padding-top: 50px;
                padding-bottom: 20px;
            }

            .container {
                width: auto;
                max-width: 680px;
                padding: 0 15px;
            }
        </style>

        <script type="text/javascript">
            var intCountDown = 3;

            function countDown() {
                document.getElementById('cdown').innerHTML = intCountDown;

                if (intCountDown <= 0) {
                    document.frm_rds_go.submit();
                    return;
                }
                intCountDown--;

                setTimeout("countDown()", 1000);
            }
        </script>
    </head>

    <body onload="countDown();">
        <div class="container">
            <div align="center">
                <img class="img-responsive" src="/images/2017/dxn-brand.png" alt="DXN-Brand" style="width: 400px"></img>
            </div>
            <br/>

            <?php
            if ($errorMsg) {
                ?>
                <div>
                    <?= $errorMsg; ?><br>
                        <input type="button" value="Back" onclick="window.location.href = '/index.php?r=payment/payselect';" />
                </div>
                <?
            } else {
                ?>

                <table class="table table-bordered" bgcolor="#cccccc" align="center" border="0" cellspacing="1" cellpadding="2">
                    <tr bgcolor="#e6e6e6">            
                        <td valign="middle" colspan="2"><b><?= Yii::t('onlinepurchase', 'Redsys Payment Information :') ?></b></td>                
                    </tr>

                    <tr bgcolor="#ffffff" >
                        <td align="left" valign="middle" width="20%">&nbsp;<?= Yii::t('epoint', 'Invoice No') ?></td>
                        <td align="left" valign="middle">&nbsp;<?= $form_reff ?></td>
                    </tr>
                    <tr bgcolor="#ffffff" >
                        <td align="left" valign="middle" width="20%">&nbsp;<?= Yii::t('epoint', 'Amount') ?> (EUR)</td>
                        <td align="left" valign="middle">&nbsp;<?= number_format($form_amt, 2) ?></td>
                    </tr>
                    <tr bgcolor="#ffffff" >
                        <td align="left" valign="middle" colspan="2">&nbsp;<font color="orange"><b><?= Yii::t('epoint', 'Please do not close the browser or click Back or Stop or Refresh button.') ?></b></font></td>
                    </tr>
                    <tr bgcolor="#ffffff" >
                        <td align="left" valign="middle" colspan="2">&nbsp;<?= Yii::t('onlinepurchase', 'You will be redirected to Redsys payment page') . " ..." ?>&nbsp;<label id="cdown"></label></td>
                    </tr>
                </table>

                <form method="post" name="frm_rds_go" id="frm_rds_go" action="<?= $config->rds_url ?>">
                    <input type="hidden" name="Ds_SignatureVersion" value="<?= $Ds_SignatureVersion ?>"/>
                    <input type="hidden" name="Ds_MerchantParameters" value="<?= $Ds_MerchantParameters ?>"/>
                    <input type="hidden" name="Ds_Signature" value="<?= $Ds_Signature ?>"/>
                    <!-- <button type="submit" name="submit">Submit</button> -->
                </form>
            <?php } ?>
        </div>
    </body>
</html>
