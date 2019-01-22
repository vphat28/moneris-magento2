<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\Masterpass\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ProcCountry implements ArrayInterface
{
    public function toOptionArray()
    {
        $paymentActions = [
            'CA' => 'Canada',
            'US' => 'USA',
        ];
        return $paymentActions;
    }
}
