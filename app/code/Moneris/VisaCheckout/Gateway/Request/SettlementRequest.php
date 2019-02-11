<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

class SettlementRequest extends AbstractRequest implements BuilderInterface
{
    /**
     * Builds request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $this->getValidPaymentInstance($buildSubject);
        $request = $this->requestDataBuilder->buildSettlementRequestData();

        return (array) $request;
    }
}
