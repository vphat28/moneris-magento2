<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Api;

use Magento\Framework\Session\SessionManagerInterface;

class Hosted extends \Moneris\CreditCard\Controller\Hosted
{
    /**
     * @var \Moneris\CreditCard\Model\Method\Hosted
     */
    private $hostedMethod;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

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
        \Moneris\CreditCard\Model\Method\Hosted $hostedMethod,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\Order\Status $status
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
            $orderModel,
            $status
        );

        $this->hostedMethod = $hostedMethod;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        //check Async Data
        $this->_delayProcess(self::MAX_TRIES);

        // Initialize return url
        $returnUrl = $this->getCheckoutHelper()->getUrl('checkout');
        // Get params from response
        $params = $this->getRequest()->getParams();
        if (!empty($params['trans_name']) && strpos($params['trans_name'], 'idebit_purchase') !== false) {
            $this->checkoutSession->setResponseError($params);
            $returnUrl = $this->getCheckoutHelper()->getUrl('moneriscc/index/error');
        }
        
        $messageResponse = 'Payment could not be processed at this time. Please try again later.';
        if (!empty($params) && !empty($params['response_code'])) {
            $arrayError = $this->getCheckoutHelper()->hostedResponse;
            if (array_key_exists($params['response_code'], $arrayError)) {
                $messageResponse = $arrayError[$params['response_code']];
            }
        }

        try {
            $returnUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
            $paymentMethod = $this->getPaymentMethod();
            // Get payment method code
            $code = $paymentMethod->getCode();
            $customerSession = $this->getCustomerSession();
            if ($params && !empty($params['response_code']) && (int) $params['response_code'] < 50) {
            // Get quote from session
                $quoteId = $this->getQuote()->getId();
                $quote = $this->quote->load($quoteId);
                $responseId = $params['response_order_id'];
                $responseId = explode("-", $responseId);
                
                $check = true;
                if ($quote->getBankTransactionId() &&
                    $quote->getBankTransactionId() != $params['bank_transaction_id']
                ) {
                    $check = false;
                }
                
                if (!empty($responseId) && $responseId[0] == $quoteId && $check) {
                    if (!$customerSession->isLoggedIn()) {
                        $quote->setCustomerIsGuest(1);
                        $quote->setCheckoutMethod('guest');
                        $quote->setCustomerId(null);
                        $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
                        $quote->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                        $customerId = 0;
                    } else {
                        $customerId = $customerSession->getId();
                    }
                
                    $quote->setPaymentMethod($code);
                    $quote->setInventoryProcessed(false);
                    $quote->save();
                    $quote->getPayment()->importData(
                        [
                            'method' => $code,
                            'last_trans_id' => $params['bank_transaction_id'],
                            'cc_trans_id' => $params['bank_transaction_id']
                        ]
                    );
                    // Collect Totals & Save Quote
                    $quote->collectTotals()->save();
                    $quoteId = $quote->getId();
                    // Create Order From Quote
                    $order = $this->quoteManagement->submit($quote);
                    $payment = $order->getPayment();
                    $transId = $params['bank_transaction_id'];
                    if (!empty($params['txn_num'])) {
                        $transId = $params['txn_num'];
                    }
                
                    $payment->setCcTransId($transId);
                    $payment->setCcApproval($params['bank_approval_code']);
                    $payment->setAdditionalInformation('trans_name', $params['trans_name']);
                    $payment->setAdditionalInformation('bank_transaction_id', $params['bank_transaction_id']);
                    $payment->setAdditionalInformation('receipt_id', $params['response_order_id']);
                    
                    if ($params['trans_name'] && strpos($params['trans_name'], 'idebit_purchase') !== false) {
                        $returnUrl = $this->getCheckoutHelper()->getUrl('moneriscc/index/success');
                        $payment->setAdditionalInformation('ISSNAME', $params['ISSNAME']);
                        $payment->setAdditionalInformation('ISSCONF', $params['ISSCONF']);
                    }
                
                    $payment->save();
                
                    $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
                    $this->checkoutSession->setLastQuoteId($quote->getId());
                    $this->checkoutSession->setLastOrderId($order->getId());
                    $this->checkoutSession->setIncrementId($order->getIncrementId());
                    $this->checkoutSession->setLastOrderStatus($order->getStatus());
                    $this->checkoutSession->setLastRealOrderId($order->getRealOrderId());
                    $this->cart->truncate()->save();
                
                    $params['real_order_id'] = $order->getRealOrderId();
                    $params['bank_transaction_id'] = $transId;
                    $this->hostedMethod->process($params);
                    
                    $this->_setOrderStatus($order);
                    $this->getResponse()->setRedirect($returnUrl);
                } else {
                    $returnUrl = $this->getCheckoutHelper()->getUrl('checkout');
                }
            } else {
                $returnUrl = $this->getCheckoutHelper()->getUrl('checkout');
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, __($messageResponse));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __($messageResponse));
        }

        $this->getResponse()->setRedirect($returnUrl);
    }

    public function _setOrderStatus($order)
    {
        $orderStatus = $this->status->loadDefaultByState('processing')->getStatus();
        if (!empty($orderStatus)) {
            $order->setState('processing')->setStatus($orderStatus);
            try {
                $order->save();
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
            }
        }
    }

    public function _delayProcess($time = self::MAX_TRIES)
    {
        $quoteId = $this->getQuote()->getId();
        $quote = $this->quote->load($quoteId);
        $start = time();
        while (true) {
            $end = time();

            if ($quote->getBankTransactionId()) {
                break;
            } else {
                if (($end - $start) >= $time) {
                    break;
                }
            }
        }
    }
}
