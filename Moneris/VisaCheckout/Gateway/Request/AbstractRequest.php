<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Gateway\Request;

use Moneris\VisaCheckout\Gateway\Config\Config;
use Moneris\VisaCheckout\Gateway\Helper\SubjectReader;
use Moneris\VisaCheckout\Helper\RequestDataBuilder;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;

abstract class AbstractRequest
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var RequestDataBuilder
     */
    protected $requestDataBuilder;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    public function __construct(
        Config $config,
        RequestDataBuilder $requestDataBuilder,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->subjectReader = $subjectReader;
    }

    protected function getValidPaymentInstance(array $buildSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        ContextHelper::assertOrderPayment($payment);

        return $payment;
    }
}
