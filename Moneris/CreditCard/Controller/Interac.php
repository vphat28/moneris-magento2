<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Moneris\CreditCard\Controller;

use Moneris\CreditCard\Model\Method\Interac as InteracPayment;
use Magento\Framework\Session\SessionManagerInterface;

abstract class Interac extends \Magento\Framework\App\Action\Action
{
    const IDEBIT_TRACK2 = "IDEBIT_TRACK2";
    
    const IDEBIT_INVOICE = "IDEBIT_INVOICE";
    
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
     * @var \Moneris\CreditCard\Model\Method\Interac
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
     * Interac constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param SessionManagerInterface $customerSession
     * @param SessionManagerInterface $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param InteracPayment $paymentMethod
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
        \Moneris\CreditCard\Model\Method\Interac $paymentMethod,
        \Moneris\CreditCard\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\Order $orderModel
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
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderById($order_id)
    {
        $order_info = $this->orderModel->loadByIncrementId($order_id);
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
    
    public function getPaymentData()
    {
        $quote = $this->getQuote();
        $grandTotal = floor($quote->getGrandTotal() * 100);
    
        $params = [
            'IDEBIT_AMOUNT' => $grandTotal,
            'IDEBIT_INVOICE' => $quote->getId(), //$this->getCheckoutHelper()->generateUniqueId($quote, 10),
            //====== prepare Data
            'IDEBIT_MERCHNUM' => $this->getCheckoutHelper()->getConfigData("payment/chmonerisinterac/merchant_id"),
            'IDEBIT_CURRENCY' => "CAD",
            'IDEBIT_MERCHLANG' => "en",
            'IDEBIT_ISSLANG' => "en",
            'IDEBIT_VERSION' => '1',
            'IDEBIT_MERCHDATA' => $quote->getId(), //this is not strictly used, we will just test for it on return
            //===== prepare Optional Details
            'IDEBIT_FUNDEDURL' => $this->getCheckoutHelper()->getUrl('moneriscc/interac/funded'),
            'IDEBIT_NOTFUNDEDURL' => $this->getCheckoutHelper()->getUrl('moneriscc/interac/nonfunded'),
            'request_url' => $this->getMonerisRequestUrl()
        ];
        $this->getCheckoutHelper()->log(
            __METHOD__ . __LINE__ ." request to interac: " . print_r($params, 1)
        );
        return $params;
    }

    public function getMonerisRequestUrl()
    {
        if ($this->getCheckoutHelper()->getConfigData('payment/chmonerisinterac/environment') ===
            InteracPayment::PAYMENT_ENVIRONMENT_CERTIFICATION_TEST
        ) {
            return 'https://merchant-test.interacidebit.ca/gateway/merchant_certification_processor.do';
        }
        
        if ($this->getCheckoutHelper()->getConfigData('payment/chmonerisinterac/environment') ===
            InteracPayment::PAYMENT_ENVIRONMENT_PROCDUCTION
        ) {
            return 'https://gateway.interaconline.com/merchant_processor.do';
        }
        
        return 'https://merchant-test.interacidebit.ca/gateway/merchant_test_processor.do';
    }
}
