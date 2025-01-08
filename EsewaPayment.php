<?php
class EsewaPayment {
    private const MERCHANT_ID = 'EPAYTEST';
    private const TEST_URL = 'https://uat.esewa.com.np/epay/main';
    private const TEST_VERIFY_URL = 'https://uat.esewa.com.np/epay/transrec';
    
    private $amount;
    private $productCode;
    private $successUrl;
    private $failureUrl;
    
    public function __construct($amount, $productCode) {
        $this->amount = number_format($amount, 2, '.', '');
        $this->productCode = htmlspecialchars($productCode, ENT_QUOTES, 'UTF-8');

        // Use full URLs for success and failure endpoints
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . 
                $_SERVER['HTTP_HOST'] . 
                dirname($_SERVER['PHP_SELF']);

        $this->successUrl = $baseUrl . '/esewa-success.php';
        $this->failureUrl = $baseUrl . '/esewa-failure.php';
    }
    
    public function getPaymentForm() {
        $formInputs = [
            'amt' => $this->amount,  
            'pdc' => '0',           
            'psc' => '0',           
            'txAmt' => '0',         
            'tAmt' => $this->amount, 
            'pid' => $this->productCode,
            'scd' => self::MERCHANT_ID,
            'su' => $this->successUrl,
            'fu' => $this->failureUrl
        ];
        
        $form = sprintf('<form action="%s" method="POST" id="esewaForm">', self::TEST_URL);
        
        foreach ($formInputs as $name => $value) {
            $form .= sprintf(
                '<input type="hidden" name="%s" value="%s">',
                htmlspecialchars($name),
                htmlspecialchars($value)
            );
        }
        
        $form .= '</form>';
        return $form;
    }
    
    public function verifyPayment($refId, $oid) {
        $args = [
            'rid' => $refId,
            'pid' => $oid,
            'scd' => self::MERCHANT_ID
        ];
        
        $url = self::TEST_VERIFY_URL . '?' . http_build_query($args);
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        return $status === 200;
    }
}