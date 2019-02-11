<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Source;

use Magento\Framework\Option\ArrayInterface;
use Moneris\CreditCard\Model\Method\Interac;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PaymentEnvironment implements ArrayInterface
{
    public function toOptionArray()
    {
        $paymentEnvironment = [
            Interac::PAYMENT_ENVIRONMENT_PROCDUCTION => __('Production Environment'),
            Interac::PAYMENT_ENVIRONMENT_DEVELOPMENT => __('Development Environment'),
            Interac::PAYMENT_ENVIRONMENT_CERTIFICATION_TEST => __('Certification Test Tool '),
        ];
        return $paymentEnvironment;
    }
}
