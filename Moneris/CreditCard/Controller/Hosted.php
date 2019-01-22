<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Moneris\CreditCard\Controller;

use Magento\Framework\Session\SessionManagerInterface;

abstract class Hosted extends \Magento\Framework\App\Action\Action
{
    const MAX_TRIES = 6;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    public $quoteRepository;
    
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    public $quoteManagement;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    public $quote;

    /**
     * @var \Moneris\CreditCard\Model\Method\Hosted
     */
    public $paymentMethod;

    /**
     * @var \Moneris\CreditCard\Helper\Data
     */
    public $checkoutHelper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public $resultJsonFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    public $cart;

    /**
     * @var \Magento\Sales\Model\Order
     */
    public $orderModel;
    
    /**
     * @var \Magento\Sales\Model\Order\Status
     */
    public $status;
    
    /**
     * Hosted constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param SessionManagerInterface $customerSession
     * @param SessionManagerInterface $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Moneris\CreditCard\Model\Method\Hosted $paymentMethod
     * @param \Moneris\CreditCard\Helper\Data $checkoutHelper
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Sales\Model\Order $orderModel
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        SessionManagerInterface $customerSession,
        SessionManagerInterface $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Moneris\CreditCard\Model\Method\Hosted $paymentMethod,
        \Moneris\CreditCard\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Sales\Model\Order\Status $status
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->orderFactory = $orderFactory;
        $this->paymentMethod = $paymentMethod;
        $this->checkoutHelper = $checkoutHelper;
        $this->cart = $cart;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->orderModel = $orderModel;
        $this->status = $status;
        parent::__construct($context);
    }

    /**
     * Instantiate quote and checkout
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initCheckout()
    {
        $quote = $this->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t initialize checkout.'));
        }
    }

    /**
     * Cancel order, return quote to customer
     *
     * @param string $errorMsg
     * @return false|string
     */
    public function _cancelPayment($errorMsg = '')
    {
        $gotoSection = false;
        $this->checkoutHelper->cancelCurrentOrder($errorMsg);
        if ($this->checkoutSession->restoreQuote()) {
            //Redirect to payment step
            $gotoSection = 'paymentMethod';
        }

        return $gotoSection;
    }

    /**
     * Get order object
     *
     * @param $orderId
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderById($orderId)
    {
        $order_info = $this->orderModel->loadByIncrementId($orderId);
        return $order_info;
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId()
        );
    }

    public function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->getCheckoutSession()->getQuote();
        }
        
        return $this->quote;
    }

    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function getCheckoutHelper()
    {
        return $this->checkoutHelper;
    }
}
