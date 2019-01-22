<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Source;

/**
 * Class CcType
 * @codeCoverageIgnore
 */
class Cctype extends \Magento\Payment\Model\Source\Cctype
{

    /**
     * Config
     *
     * @param \Magento\Payment\Model\Config $paymentConfig
     */
    public function __construct(\Magento\Payment\Model\Config $paymentConfig)
    {
        parent::__construct($paymentConfig);
    }

    /**
     * Allowed credit card types
     *
     * @return string[]
     */
    public function getAllowedTypes()
    {
        return ['VI', 'MC', 'AE', 'DI', 'JCB', 'DN', 'UN'];
    }
}
