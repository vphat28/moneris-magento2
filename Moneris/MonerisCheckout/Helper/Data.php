<?php

namespace Moneris\MonerisCheckout\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Data
{
    /** @var ScopeConfigInterface */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $storeConfig)
    {
        $this->scopeConfig = $storeConfig;
    }

    public function getStoreId()
    {
        return 'monca03257';
    }

    public function getApiToken()
    {
        return 'z5KGL0UI9MdnnL8ef0Nz';
    }

    public function getCheckoutId()
    {
        return 'chktNDMNN03257';
    }
}
