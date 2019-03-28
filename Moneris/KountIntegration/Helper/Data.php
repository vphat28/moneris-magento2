<?php

namespace Moneris\KountIntegration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Data
{
    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var EncryptorInterface */
    private $encryptor;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    public function getAPIKey($store = null)
    {
        return $this->encryptor->decrypt(
            $this->scopeConfig->getValue('payment/chmoneriskount/api_key', ScopeInterface::SCOPE_STORE, $store)
        );
    }

    public function isEnable($store = null)
    {
        return $this->scopeConfig->isSetFlag('payment/chmoneriskount/enable', ScopeInterface::SCOPE_STORE, $store);
    }

    public function getSiteID($store = null)
    {
        return $this->scopeConfig->getValue('payment/chmoneriskount/site_id', ScopeInterface::SCOPE_STORE, $store);
    }

    public function getMerchantID($store = null)
    {
        return $this->scopeConfig->getValue('payment/chmoneriskount/merchant_id', ScopeInterface::SCOPE_STORE, $store);
    }
}