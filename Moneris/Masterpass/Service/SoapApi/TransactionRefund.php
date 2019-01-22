<?php

namespace Moneris\Masterpass\Service\SoapApi;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class TransactionRefund
 */
class TransactionRefund extends \Moneris\Masterpass\Service\Transaction implements ClientInterface
{
    
    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $session;
    
    /**
     *
     * @var \Magento\Sales\Model\Order\Payment\Repository
     */
    private $orderPaymentRepository;
    
    public function __construct(
        \Moneris\Masterpass\Gateway\Config\Config $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\Order\Payment\Repository $orderPaymentRepository,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->session = $session;
        $this->orderPaymentRepository = $orderPaymentRepository;
        parent::__construct($scopeConfig, $logger, $urlBuilder);
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     * @throws LocalizedException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $this->_logger->info("transaction refund");
        $this->_logger->info("request body = ".print_r($transferObject->getBody(), 1));
        $response = $this->mpgRequest($transferObject->getBody());
        return ['response' => $response];
    }
}
