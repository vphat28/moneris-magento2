<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\Masterpass\Model\Source;

use Magento\Framework\Option\ArrayInterface;
use Moneris\CreditCard\Model\Method\Payment;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PaymentAction implements ArrayInterface
{
    public function toOptionArray()
    {
        $paymentActions = [
            Payment::PAYMENT_ACTION_AUTH => __('Authorise'),
            Payment::PAYMENT_ACTION_CAPTURE => __('Authorise and Capture'),
        ];
        return $paymentActions;
    }
}
