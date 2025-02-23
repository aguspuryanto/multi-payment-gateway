<?php

// GCash
// https://www.checkout.com/docs/payments/add-payment-methods/gcash
// https://gist.github.com/domdanao/849cbb7cac1ec8baaec9c37b35d2f80a
// https://miniprogram.gcash.com/docs/miniprogram_gcash/mpdev/v1_pay
// https://docs.adyen.com/payment-methods/gcash/api-only/

use app\components\CurlTool;

class GoCashGateway {

    private $config = [];

    const HTTP_TIMEOUT = 6.0;
    const SIGN_TYPE_RSA = 'RSA_1_256';
    // Request URL: https://gateway.wepayez.com/pay/gateway
    const GATEWAY = "https://gateway.wepayez.com/pay/gateway";

    public function __construct(array $config) {
        if (empty($config['sign_type']) && empty($config['mch_key'])) {
            throw new Exception('When the sign_type is set to MD5 encryption, the mch_key cannot be empty.');
        }

        if ($config['sign_type'] === self::SIGN_TYPE_RSA && empty($config['private_key']) && empty($config['platform_public_key'])) {
            throw new Exception('When the sign_type is set to RSA_1_256 encryption, the private_key and platform_public_key cannot be empty.');
        }

        if (empty($config['notify_url'])) {
            throw new Exception('"The notify_url cannot be empty.');
        }

        $this->config = $config;
    }

    public function pay(array $data) {
        // $data['service'] = 'pay.weixin.jspay';
        // $data['service'] = 'pay.weixin.native';
        $data['service'] = 'unified.checkout.prepay';
        $data = array_merge($data, [
            'mch_create_ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1',
            'notify_url' => $this->config['notify_url'],
        ]);

        if(!empty($this->config['callback_url'])) {
            $data['callback_url'] = $this->config['callback_url'];            
        }
        
        // echo json_encode($data);
        return $this->_post($data);
    }

    public function getPayLink($tokenId, $showWxTitle = 1) {
        return "https://payapi.citicbank.com/pay/jspay?token_id={$tokenId}&showwxtitle={$showWxTitle}";
    }

    public function refund(array $data) {
        $data['service'] = 'unified.trade.refund';
        $data = array_merge($data, ['op_user_id' => $this->config['mch_id']]);

        return $this->_post($data);
    }

    public function getTradeDetail(array $data) {
        $data['service'] = 'unified.trade.query';

        return $this->_post($data);
    }

    public function getRefundDetail(array $data) {
        $data['service'] = 'unified.trade.refundquery';

        return $this->_post($data);
    }

    public function isValidSign($sign, $data) {
        if (isset($data['sign_type']) && $data['sign_type'] === self::SIGN_TYPE_RSA) {
            return openssl_verify($this->_getRSASign($data), base64_decode($sign), $this->config['platform_public_key'], OPENSSL_ALGO_SHA256) === 1;
        }
        else {
            return $sign === $this->_getMD5Sign($data);
        }
    }

    private function _getNonceStr() {
        return md5(random_bytes(16));
    }

    private function _getSignStr($data) {
        if (is_array($data)) {
            ksort($data);
        }

        // sign不参与签名
        unset($data['sign']);
        $str = '';
        foreach ($data as $key => $value) {
            $str .= "{$key}={$value}&";
        }

        return $str;
    }

    private function _getMD5Sign($data) {
        $str = $this->_getSignStr($data) . "key={$this->config['mch_key']}";
        return strtoupper(md5($str));
    }

    private function _getRSASign($data) {
        $str = rtrim($this->_getSignStr($data), '&');
        openssl_sign($str, $signature, $this->config['private_key'], OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    private function _post(array $data) {
        // $Client = new CurlTool([
        //     'CURLOPT_TIMEOUT' => self::HTTP_TIMEOUT,
        //     'CURLOPT_HTTPHEADER' => ['Content-Type: text/xml'],
        //     'CURLOPT_CUSTOMREQUEST' => 'POST',
        // ]);
        // $Response = $Client->fetchContent(self::GATEWAY, $this->_prepare($data));

        $Response = $this->_postCurl($data);
        return $Response;
    }

    private function _prepare(array $data) {
        $data['mch_id'] = $this->config['mch_id'];
        $data['nonce_str'] = $this->_getNonceStr();
        if (isset($this->config['sign_type']) && $this->config['sign_type'] === self::SIGN_TYPE_RSA) {
            $data['sign_type'] = self::SIGN_TYPE_RSA;
            $data['sign'] = $this->_getRSASign($data);
        }
        else {
            $data['sign'] = $this->_getMD5Sign($data);
        }

        // echo json_encode($data);
        return $this->_arrayToXML($data);
    }

    private function _arrayToXML($data) {
        $xml = new SimpleXMLElement('<xml/>');
        foreach ($data as $key => $value) {
            $xml->addChild($key, $value);
        }

        return $xml->asXML();
    }

    private function _postCurl(array $data) {
        $ch = curl_init();
        if (!$ch) {
            die("Couldn't initialize a cURL handle");
        }
        curl_setopt($ch, CURLOPT_URL, self::GATEWAY);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_prepare($data));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch); // execute
        curl_close($ch);

        return $result;
    }

}
