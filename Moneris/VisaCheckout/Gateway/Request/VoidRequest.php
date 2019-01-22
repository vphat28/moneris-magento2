<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

class VoidRequest extends AbstractRequest implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $payment = $this->getValidPaymentInstance($buildSubject);
        $request = $this->requestDataBuilder->buildVoidRequestData($payment);

        return (array) $request;
    }
}
