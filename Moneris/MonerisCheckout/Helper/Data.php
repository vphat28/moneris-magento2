<?php

namespace Moneris\MonerisCheckout\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Data
{
    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Magento\Framework\Encryption\Encryptor */
    private $encryptor;

    /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\Encryptor $encryptor
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    public function getStoreId()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue('payment/chmonerismonerischeckout/login'));
    }

    public function getApiToken()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue('payment/chmonerismonerischeckout/password'));
    }

    public function getIsTestMode()
    {
        return $this->scopeConfig->isSetFlag('payment/chmonerismonerischeckout/test');
    }

    public function getCheckoutId()
    {
        return $this->scopeConfig->getValue('payment/chmonerismonerischeckout/checkout_id');
    }
}