<?php

class iPay88_SHA {

    const HOST = 'https://www.mobile88.com/epayment/entry.asp';

    public static function iPay88_signature($merchantKey, $merchantCode, $invoiceId, $amount, $currency) {
        return base64_encode(hex2bin(sha1($merchantKey . $merchantCode . $invoiceId . str_replace('.', '', str_replace(',', '', $amount)) . $currency)));
    }

    private static function hex2bin($hexSource) {
        for ($i = 0; $i < strlen($hexSource); $i = $i + 2) {
            $bin .= chr(hexdec(substr($hexSource, $i, 2)));
        }
        return $bin;
    }

}

class iPay88_Callback {

    var $array, $merchantKey, $host;

    public function __construct($merchantKey) {
        $this->array = array(
            'MerchantCode' => filter_var($_POST['MerchantCode'], FILTER_SANITIZE_STRING),
            'PaymentId' => filter_var($_POST['PaymentId'], FILTER_SANITIZE_STRING),
            'RefNo' => filter_var($_POST['RefNo'], FILTER_SANITIZE_STRING),
            'Amount' => filter_var($_POST['Amount'], FILTER_SANITIZE_STRING),
            'Currency' => filter_var($_POST['Currency'], FILTER_SANITIZE_STRING),
            'Remark' => filter_var($_POST['Remark'], FILTER_SANITIZE_STRING),
            'TransId' => filter_var($_POST['TransId'], FILTER_SANITIZE_STRING),
            'AuthCode' => filter_var($_POST['AuthCode'], FILTER_SANITIZE_STRING),
            'Status' => filter_var($_POST['Status'], FILTER_SANITIZE_STRING),
            'ErrDesc' => filter_var($_POST['ErrDesc'], FILTER_SANITIZE_STRING),
            'Signature' => filter_var($_POST['Signature'], FILTER_SANITIZE_STRING),
            'CCName' => filter_var($_POST['CCName'], FILTER_SANITIZE_STRING),
            'S_bankname' => filter_var($_POST['S_bankname'], FILTER_SANITIZE_STRING),
            'S_country' => filter_var($_POST['S_country'], FILTER_SANITIZE_STRING),
            'TokenId' => filter_var($_POST['TokenId'], FILTER_SANITIZE_STRING)
        );
        $this->merchantKey = $merchantKey;
    }

    /*
     * Generate iPay88 Signature for Verification
     * Indirect Use
     * 
     */

    function iPay88_signature($source) {
        return base64_encode(hex2bin(sha1($source)));
    }

    function hex2bin($hexSource) {
        for ($i = 0; $i < strlen($hexSource); $i = $i + 2) {
            $bin .= chr(hexdec(substr($hexSource, $i, 2)));
        }
        return $bin;
    }

    /*
     * By default Signature verification is enough
     * But, if want extra verification, make extra call
     * 
     */

    public function verifySignature() {
        $amount = preg_replace("/[^0-9]/", "", $this->array['Amount']);
        $string = $this->iPay88_signature($this->merchantKey . $this->array['MerchantCode'] . $this->array['PaymentId'] . $this->array['RefNo'] . $amount . $this->array['Currency'] . $this->array['Status']);
        if ($string == $this->array['Signature']) {
            return $this;
        } else {
            exit('Signature Not Match');
        }
    }

    public function requeryStatus($array = array('exit' => true)) {
        $host = 'https://www.mobile88.com/epayment/enquiry.asp';
        //$host.= '?MerchantCode='.$this->array['MerchantCode'].'&RefNo='.$this->array['RefNo']. '&Amount='.$this->array['Amount'];
        $process = curl_init();
        curl_setopt($process, CURLOPT_URL, $host);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 10);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($this->array));
        $return = curl_exec($process);
        curl_close($process);
        if ($return == '00') {
            return $this;
        } else {
            // Exit je
            if ($array['exit']) {
                exit($return);
                // Don't exit because this is user redirection
            } else {
                return $this;
            }
        }
    }

    /*
     *  Not used in callback
     */

    public function getData() {
        return $this->array;
    }

}
