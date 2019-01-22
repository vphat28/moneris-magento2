<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Interac;

use Magento\Framework\Session\SessionManagerInterface;

class Nonfunded extends \Moneris\CreditCard\Controller\Interac
{

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Nonfunded constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param SessionManagerInterface $customerSession
     * @param SessionManagerInterface $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Moneris\CreditCard\Model\Method\Interac $paymentMethod
     * @param \Moneris\CreditCard\Helper\Data $checkoutHelper
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        SessionManagerInterface $customerSession,
        SessionManagerInterface $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Moneris\CreditCard\Model\Method\Interac $paymentMethod,
        \Moneris\CreditCard\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\Order $orderModel
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $quoteRepository,
            $quoteManagement,
            $orderFactory,
            $logger,
            $paymentMethod,
            $checkoutHelper,
            $cart,
            $resultJsonFactory,
            $orderModel
        );

    }

    public function execute()
    {
        $data = $this->getRequest()->getParams();
        if (isset($data)) {
            $this->checkoutSession->setResponseError($data);
            if (isset($data['cancelTXN'])) {
                $this->messageManager->addErrorMessage($data['cancelTXN']);
            } else {
                $message = 'Payment could not be processed at this time. Please try again later.';
                $this->messageManager->addErrorMessage($message);
            }
        }

        $this->_redirect('moneriscc/index/error');
    }
}
