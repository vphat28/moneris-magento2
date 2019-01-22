<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class RefundRequest implements BuilderInterface
{
    const TRANSACTION_TYPE = "34";

    /**
     * @var ConfigInterface
     */
    private $logger;
    
    /**
     * @var ConfigInterface
     */
    private $config;
    
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        \Moneris\VisaCheckout\Gateway\Config\Config $config,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }
    
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }
        $this->logger->info("refund request");
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();
        $request = [
            'type'          => 'refund',
            'order_id'      => $payment->getOrder()->getIncrementId(),
            'amount'        => $this->formatAmount($buildSubject['amount']),
            'crypt_type'    => '5',
            'txn_number'    => $payment->getAdditionalInformation('txn_number')
        ];
        $this->logger->info("request = ".print_r($request, 1));
        return (array) $request;
    }
    
    /**
     * @param float $amount
     * @return string
     */
    private function formatAmount($amount)
    {
        if (!is_float($amount)) {
            $amount = (float) $amount;
        }
        
        return number_format($amount, 2, '.', '');
    }
}
