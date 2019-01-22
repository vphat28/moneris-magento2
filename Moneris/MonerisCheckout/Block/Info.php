<?php

namespace Moneris\MonerisCheckout\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * @return array
     */
    public function getSpecificInformation()
    {
        $info = parent::getSpecificInformation();

        return $info;
    }

    public function getIsSecureMode()
    {
        $method = $this->getMethod();
        if (!$method) {
            return true;
        }

        $store = $method->getStore();
        try {
            $methodStore = @$this->_storeManager->getStore($store);
        } catch (\Exception $e) {}

        return @$methodStore->getCode() != \Magento\Store\Model\Store::ADMIN_CODE;
    }
}
