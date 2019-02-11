<?php

namespace Moneris\Masterpass\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class Refund implements BuilderInterface
{

    
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
        \Moneris\Masterpass\Gateway\Config\Config $config,
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
            'amount'        => number_format($buildSubject['amount'], 2),
            'crypt_type'    => '5',
            'txn_number'    => $payment->getCcTransId()
        ];
        $this->logger->info("request = ".print_r($request, 1));
        return (array) $request;
    }
}
