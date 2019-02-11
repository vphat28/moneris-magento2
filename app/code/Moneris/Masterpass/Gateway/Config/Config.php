<?php
namespace Moneris\Masterpass\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    
    const KEY_ACTIVE = 'active';

    public function isActive()
    {
        return $this->getValue(self::KEY_ACTIVE);
    }
    
    public function getTitle()
    {
        return $this->getValue('title');
    }
    
    public function isTestMode()
    {
        return $this->getValue('test_mode');
    }
    
    public function getProcCountry()
    {
        return $this->getValue('proc_country');
    }
    
    public function getStoreId()
    {
        return $this->getValue('store_id');
    }
    
    public function getApiToken()
    {
        return $this->getValue('api_token');
    }
    
    public function getPaymentAction()
    {
        return $this->getValue('payment_action');
    }
}
