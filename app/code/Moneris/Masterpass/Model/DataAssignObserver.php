<?php

namespace Moneris\Masterpass\Model;

class DataAssignObserver extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    const PAYMENT_METHOD_NONCE = 'payment_method_nonce';

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $session;
    
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $session
    ) {
        $this->logger = $logger;
        $this->session = $session;
    }
    
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data = $this->readDataArgument($observer);
        $allData = $data->getData();
        $additionalData = $allData['additional_data'];
        if (is_array($additionalData)) {
            $this->session->setData('cc_data', $additionalData);
        }
    }
}
