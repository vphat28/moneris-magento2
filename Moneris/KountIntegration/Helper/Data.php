<?php

namespace Moneris\KountIntegration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Data
{
    const TEST_MODE_URL = 'https://risk.test.kount.net';
    const URL = 'https://risk.kount.net';

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

    public function getTestMode($store = null)
    {
        return $this->scopeConfig->isSetFlag('payment/chmoneriskount/test_mode', ScopeInterface::SCOPE_STORE, $store);
    }

    public function getSiteID($store = null)
    {
        return $this->scopeConfig->getValue('payment/chmoneriskount/site_id', ScopeInterface::SCOPE_STORE, $store);
    }

    public function getMerchantID($store = null)
    {
        return $this->scopeConfig->getValue('payment/chmoneriskount/merchant_id', ScopeInterface::SCOPE_STORE, $store);
    }

    public function getDataCollectorBaseUrl($store = null)
    {
        return $this->scopeConfig->getValue('payment/chmoneriskount/data_collector_url', ScopeInterface::SCOPE_STORE, $store);
    }

    public function getUrl()
    {
        if ($this->getTestMode()) {
            return self::TEST_MODE_URL;
        } else {
            return self::URL;
        }
    }
}