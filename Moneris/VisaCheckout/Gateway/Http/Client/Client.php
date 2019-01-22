<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\App\ObjectManager;

class Client implements ClientInterface
{
    
    /**
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    
    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    /**
     *
     * @var \Magento\Framework\Encryption\Encryptor
     */
    private $encryptor;
    
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Encryption\Encryptor $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        if (!class_exists('Moneris_MpgTransaction')) {
            $files = glob(BP .  '/lib/Moneris*/*.php');
            foreach ($files as $f) {
                require_once $f;
            }
        }
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     * @throws LocalizedException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $txnArray = json_decode(json_encode($transferObject->getBody()), 1);
        $this->logger->info("client txnArray: ".print_r($txnArray, 1));
        try {
            $mpgTxn = new \mpgTransaction($txnArray);
            $mpgRequest = new \mpgRequest($mpgTxn);

            $mpgRequest->setProcCountryCode("CA");
//            if ($this->getHelper()->isUsApi()) {
//                $mpgRequest->setProcCountryCode("US"); //"US" for sending transaction to US environment
//            }
            
            if ($this->scopeConfig->getValue(
                'payment/chvisa/test',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )) {
                $this->logger->info('tes mode');
                $mpgRequest->setTestMode(true);
            }

            $store_id = $this->encryptor->decrypt($this->scopeConfig->getValue(
                'payment/chmoneris/login',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ));
            $api_key = $this->encryptor->decrypt($this->scopeConfig->getValue(
                'payment/chmoneris/password',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ));
            $mpgHttpPost = new \mpgHttpsPost(
                $store_id,
                $api_key,
                $mpgRequest
            );
            $mpgResponse = $mpgHttpPost->getMpgResponse();
            $this->logger->info("mpgResponse: ".print_r($mpgResponse, 1));
            return ['response' => $mpgResponse];
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            throw new LocalizedException(__('Unable to retrieve payment information'));
        }
    }
}
