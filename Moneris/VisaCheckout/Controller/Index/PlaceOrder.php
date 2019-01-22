<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Controller\Index;

use Moneris\VisaCheckout\Helper\RequestDataBuilder;
use Moneris\VisaCheckout\Model\Ui\ConfigProvider;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManagerInterface;

class PlaceOrder extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Model\QuoteManagement $quoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    public function __construct(
        Context $context,
        SessionManagerInterface $checkoutSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $this->logger->info("start placeorder");
        $callId = $this->_request->getParam('callId');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        
        /**
         * Handle customer guest
         */
        if ($quote->getCustomerEmail() === null) {
            $quote = $this->prepareGuestQuote($quote);
        }
        
        $quote->setPaymentMethod(ConfigProvider::CODE);
        $quote->setInventoryProcessed(false);

        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => ConfigProvider::CODE]);
        $quote->getPayment()->setAdditionalInformation("callId", $callId);
        $quote->collectTotals();
        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->clearHelperData();
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $this->logger->info("step 1");
        try {
            $this->logger->info("quote id = ".$quote->getId());
            $order = $this->quoteManagement->submit($quote);
            $this->logger->info("step 2");
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());
            $this->logger->info("step 3");
            $successValidator = $this->_objectManager->get('Magento\Checkout\Model\Session\SuccessValidator');
            if (!$successValidator->isValid()) {
                $this->logger->info("step 3.1");
                return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            }
            $this->logger->info("step 4");
            $this->messageManager->addSuccessMessage('Your order has been successfully created!');
            return $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
        } catch (\Exception $e) {
            $this->logger->info("place order error: ".$e->getMessage());
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }
        return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Model\Quote
     */
    private function prepareGuestQuote(\Magento\Quote\Model\Quote $quote)
    {
        $quote->setCustomerId(null);
        $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);

        return $quote;
    }
}
