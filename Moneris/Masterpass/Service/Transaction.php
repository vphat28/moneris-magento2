<?php

namespace Moneris\Masterpass\Service;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class SoapApi
 */
class Transaction
{
    
    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    
    /**
     *
     * @var \Moneris\Masterpass\Gateway\Config\Config
     */
    protected $_config;
    
    /**
     *
     * @var \Moneris\Masterpass\Gateway\Config\Config
     */
    private $urlBuilder;
    
    /**
     *
     * @param \Moneris\Masterpass\Service\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Moneris\Masterpass\Gateway\Config\Config $config,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->_logger = $logger;
        $this->_config = $config;
        $this->urlBuilder = $urlBuilder;
        if (!class_exists('Moneris_MpgTransaction')) {
            $files = glob(BP .  '/lib/Moneris*/*.php');
            foreach ($files as $f) {
                require_once $f;
            }
        }
    }
    
    public function mpgRequest($txnArray)
    {
        $mpgTxn = new \mpgTransaction($txnArray);
        $mpgRequest = new \mpgRequest($mpgTxn);
        $mpgRequest->setProcCountryCode($this->_config->getProcCountry());
        if ($this->_config->isTestMode()) {
            $this->_logger->info("test mode");
            $response = $this->getTestResponse($txnArray);
        } else {
            $this->_logger->info("prod mode");
            $mpgRequest->setTestMode(false);
            $mpgHttpPost = new \mpgHttpsPost(
                $this->_config->getStoreId(),
                $this->_config->getApiToken(),
                $mpgRequest
            );
            $response = $mpgHttpPost->getMpgResponse();
        }
        
        return $response;
    }
    
    private function getTestResponse($txnArray)
    {
        switch ($txnArray['type']) {
            case 'paypass_send_shopping_cart':
                $redirect_url = urlencode($this->urlBuilder->getUrl('chmasterpass/index/receipt', [
                    'oauth_token'=> 'xxx',
                    'oauth_verifier' => 'xxx',
                    'checkout_resource_url' => 'https://api.mastercard.com/online/v3/checkout/xxx',
                ]));
                $xml="<?xml version=\"1.0\"?><response>"
                        . "<MPRedirectUrl>$redirect_url</MPRedirectUrl>"
                        . "<ResponseCode>001</ResponseCode>"
                        . "<MPRequestToken>req_token</MPRequestToken>"
                        . "</response>";
                break;
            case 'paypass_retrieve_checkout_data':
                $xml="<?xml version=\"1.0\"?><response>"
                    . "<ReceiptId>123</ReceiptId>"
                    . "<ResponseCode>001</ResponseCode>"
                    . "<AuthCode>001</AuthCode>"
                    . "<MPRequestToken>123</MPRequestToken >"
                    . "<TransAmount>123-".time()."</TransAmount >"
                    . "</response>";
                break;
            case 'refund':
                $xml="<?xml version=\"1.0\"?><response>"
                    . "<ReceiptId>123</ReceiptId>"
                    . "<ResponseCode>027</ResponseCode>"
                    . "<AuthCode>001</AuthCode>"
                    . "<ReferenceNum>123</ReferenceNum >"
                    . "<Complete>1</Complete>"
                    . "<TransID>tr-id-123-".time()."</TransID>"
                    . "</response>";
                break;
            case 'paypass_preauth':
            case 'paypass_purchase':
                $xml="<?xml version=\"1.0\"?><response>"
                    . "<ReceiptId>123</ReceiptId>"
                    . "<ResponseCode>027</ResponseCode>"
                    . "<AuthCode>001</AuthCode>"
                    . "<ReferenceNum>123</ReferenceNum >"
                    . "<Complete>1</Complete>"
                    . "<TransID>tr-id-123-".time()."</TransID>"
                    . "</response>";
                break;
        }
        return new \mpgResponse($xml);
    }
}
