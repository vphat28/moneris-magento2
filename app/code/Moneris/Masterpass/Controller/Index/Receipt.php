<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\Masterpass\Controller\Index;

use Moneris\Masterpass\Model\Ui\ConfigProvider;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Receipt extends \Magento\Framework\App\Action\Action
{
    
    /**
     *
     * @var \Moneris\Masterpass\Gateway\Config\Config
     */
    protected $_config;
    
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
     * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        JsonFactory $resultJsonFactory,
        \Psr\Log\LoggerInterface $logger,
        \Moneris\Masterpass\Service\Transaction $transaction,
        \Moneris\Masterpass\Gateway\Config\Config $config,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->transaction = $transaction;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->_config = $config;
    }

    public function execute()
    {
        $this->logger->info("start receipt");
        $oauth_token = $this->_request->getParam('oauth_token');
        $oauth_verifier = $this->_request->getParam('oauth_verifier');
        $checkoutId = $this->_request->getParam('checkoutId');
        $checkout_resource_url = $this->_request->getParam('checkout_resource_url');
        $mpstatus = $this->_request->getParam('mpstatus');
        $verifyRequest = [
            'type' => 'paypass_retrieve_checkout_data',
            'oauth_token' => $oauth_token,
            'oauth_verifier' => $oauth_verifier,
            'checkout_resource_url' => $checkout_resource_url
        ];
        $this->logger->info("verify request = ".print_r($verifyRequest, 1));
        $verifyResponse = $this->transaction->mpgRequest($verifyRequest);
        $this->logger->info("verify response = ".print_r($verifyResponse, 1));
        if (!empty($verifyResponse)) {
            $quote = $this->checkoutSession->getQuote();
            $quote->reserveOrderId();
            $processRequest = [
                'type'=> ($this->_config->getPaymentAction() == 'authorize') ? 'paypass_preauth' : 'paypass_purchase',
                'order_id' => $quote->getReservedOrderId(),
//                'cust_id'=>'customer2',
                'amount' => $quote->getGrandTotal(),
                'crypt_type' => '7',
                'mp_request_token' => $this->checkoutSession->getMpToken(),
//                'dynamic_descriptor'=>'123456'
            ];
            $this->logger->info("process request = ".print_r($processRequest, 1));
            $processResponse = $this->transaction->mpgRequest($processRequest);
            $this->logger->info("process response = ".print_r($processResponse, 1));
            if (!empty($processResponse) && $processResponse->getResponseCode() == '027') {
                return $this->_redirect($this->placeOrder($processResponse));
            } else {
                $this->messageManager->addErrorMessage("gateway error #".$processResponse->getResponseCode());
                return $this->_redirect('checkout/cart');
            }
        }
    }
    
    private function placeOrder($processResponse)
    {
        $this->logger->info("start placeorder");
        $url = 'checkout/cart';
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        
        /**
         * Handle customer guest
         */
        if ($quote->getCustomerEmail() === null) {
            $this->prepareGuestQuote($quote);
        }
        
        $quote->setPaymentMethod(\Moneris\Masterpass\Model\Ui\ConfigProvider::MCODE);
        $quote->setInventoryProcessed(false);

        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => \Moneris\Masterpass\Model\Ui\ConfigProvider::MCODE]);
        $quote->getPayment()->setTransactionId($processResponse->getTxnNumber());
        $quote->collectTotals()->save();
        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->clearHelperData();
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $this->logger->info("step 1");
        try {
            $this->logger->info("quote id = ".$quote->getId());
            $order = $this->quoteManagement->submit($quote);
            $payment = $order->getPayment();
            $payment->setCcTransId($processResponse->getTxnNumber());
            $payment->setLastTransId($processResponse->getTxnNumber());
            $this->orderPaymentRepository->save($payment);
            $this->updateInvoice($order, $processResponse);
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
            $this->logger->info("txn number = ".$processResponse->getTxnNumber());
            $url = 'checkout/onepage/success';
        } catch (\Exception $e) {
            $this->logger->info("place order error: ".$e->getMessage());
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }
        return $url;
    }
    
    private function updateInvoice($order, $processResponse)
    {
        $invoices = $order->getInvoiceCollection();
        if (!empty($invoices)) {
            foreach ($invoices as $invoice) {
                $invoice->setTransactionId($processResponse->getTxnNumber());
                $invoice->save();
                break;
            }
        }
    }
    
    /**
     * Prepare quote for guest checkout order submit
     *
     * @param Quote $quote
     * @return void
     */
    private function prepareGuestQuote($quote)
    {
        $this->logger->info("guest email = ".$this->checkoutSession->getGuestEmail());
        $quote->setCustomerId(null);
        $quote->setCustomerEmail($this->checkoutSession->getGuestEmail());
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
    }
}
