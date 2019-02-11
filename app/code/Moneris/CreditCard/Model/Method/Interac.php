<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Method;

use Moneris\CreditCard\Helper\Data as chHelper;
use Moneris\CreditCard\Model\Transaction;
use Moneris\CreditCard\Model\AbstractModel as MonerisConstants;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\TransparentInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order as OrderModel;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Interac extends AbstractPayment implements TransparentInterface, ConfigInterface
{
    const METHOD_CODE = 'chmonerisinterac';

    const STATUS_ERROR = 'ERROD';
    const PAYMENT_ENVIRONMENT_PROCDUCTION = 'P';
    const PAYMENT_ENVIRONMENT_DEVELOPMENT = 'D';
    const PAYMENT_ENVIRONMENT_CERTIFICATION_TEST = 'C';

    const GATEWAY_ACTIONS_LOCKED_STATE_KEY = 'is_gateway_actions_locked';
    const CAPTURE = 'capture';
    const PAYMENT_ACTION_CAPTURE = 'authorize_capture';
    const PAYMENT_ACTION_AUTH = 'authorize';
    const PAYMENT_PURCHASE = 'authorize';
    const  REQUEST_METHOD_CC = 'CC' ;
    const  REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
    const  REQUEST_TYPE_AUTH_ONLY = 'AUTH_ONLY';
    const  REQUEST_TYPE_CAPTURE_ONLY = 'CAPTURE_ONLY';
    const  REQUEST_TYPE_CREDIT = 'CREDIT';
    const  REQUEST_TYPE_VOID = 'VOID';

    const REAL_TRANSACTION_ID_KEY = 'real_transaction_id';

    protected $_code = self::METHOD_CODE;
    protected $_canAuthorize = true;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;

    protected $amount;
    protected $payment;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var OrderSender
     */
    protected $orderSender;


    /**
     * @var chHelper
     */
    protected $chHelper;

    /**
     * Order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    protected function log($x, $lineNumber = null)
    {
        if ($x instanceof \Magento\Framework\DataObject) {
            $x = $x->getData();
        }

        $content = __CLASS__ . ($lineNumber ? ":{$lineNumber}" : '') . " " .  print_r($x, true);
        $this->getHelper()->log($content);

        return $this;
    }

    public function getHelper()
    {
        return $this->chHelper;
    }

    public function isPurchase()
    {
        return false;
        $action = $this->getHelper()->getPaymentAction();
        return $action != self::PAYMENT_ACTION_AUTH && $action != self::PAYMENT_ACTION_CAPTURE;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->getConfigData('merchant_id')
            || !$this->getHelper()->getIsFrontend()
            ) {
                return false;
        }

        return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currency) {
        return true;
    }
    
    /**
     * Do not validate payment form using server methods
     *
     * @return bool
     */
    public function validate()
    {
        return true;
    }

    /**
     * Send authorize request to gateway
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface $payment
     * @param  float $amount
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->payment = $payment;
        $this->amount = $amount;
        return $this->processTransaction(self::PAYMENT_ACTION_AUTH);
    }

    /**
     * Send capture request to gateway
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->payment = $payment;
        $this->amount = $amount;
        return $this->processTransaction(self::PAYMENT_ACTION_CAPTURE);
    }

    /**
     * processTransaction
     */
    public function processTransaction($paymentType = self::PAYMENT_ACTION_AUTH)
    {
        $this->payment->setAdditionalInformation('payment_type', $this->getConfigData('payment_action'));
        $this->amount = $this->_getFormattedAmount($this->amount);

        if ($this->amount < 0) {
            $this->getHelper()->handleError(__("Invalid amount to process: [{$this->amount}]"), true);
        }

        $isCompletion = $paymentType != self::PAYMENT_ACTION_AUTH && $this->payment->getCcTransId();
        $cryptType = Transaction::CRYPT_SEVEN;
        if ($paymentType == self::PAYMENT_ACTION_AUTH) {
            $this->log(__METHOD__ . __LINE__ ." success ! ".print_r($cryptType, 1));
        } else {
            if ($isCompletion && !$this->getHelper()->getIsFrontend()) {
                $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Interac\Completion');
                $transaction->setPayment($this->payment)->setAmount($this->amount);
                $result = $transaction->post();
                $this->getHelper()->log(__METHOD__ . __LINE__ . ' Result from Moneris purchase: ' . print_r($result, 1));
                if ($result->getError()) {
                    $error = __("Error in processing payment: {$paymentType} " . $result->getResponseText());
                    $this->getHelper()->handleError($error, true);
                }
                
                if (!$result->getSuccess()) {
                    $this->getHelper()->handleError(__('Error in processing payment: '
                        . $result->getResponseText()), true);
                } else {
                    $this->log(__METHOD__ . " success ! ");
                }
            }
        }

        if (self::PAYMENT_ACTION_AUTH != $paymentType) {
            $this->payment->setIsTransactionClosed(0)
            ->setTransactionAdditionalInfo(
                self::REAL_TRANSACTION_ID_KEY,
                $this->payment->getLastTransId()
            );
        }
        
        return $this;
    }

    /**
     * Sends a refund transaction.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment, float $amount
     * @return $this
     * @throws Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->payment = $payment;
        $this->amount = $this->_getFormattedAmount($amount);
        if ($this->amount <= 0) {
            $error = __('Error in refunding payment: amount (%s) cannot be 0 or less.', $amount);
            $this->getHelper()->handleError($error, true);
        }
        
        $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Interac\Refund')
        ->setPayment($payment)
        ->setAmount($amount);
        $result = $transaction->post();
        if (!$result->getSuccess()) {
            $this->log(__METHOD__ . __LINE__ . ' Error result: ' . print_r($result, 1));
            $this->getHelper()->handleError(__('Error in refunding payment: ' . $result->getResponseText()), true);
        }
        
        return $this;
    }
    /**
     * Sends a void transaction.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment, float $amount
     * @return $this
     * @throws Exception
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->payment = $payment;
        if (!$payment->getLastTransId()) {
            $payment->setStatus(self::STATUS_ERROR);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid transaction id.')
            );
        }
        
        $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\VoidTransaction')
        ->setPayment($payment)
        ->setAmount(0);
        $result = $transaction->post();
        if (!$result->getSuccess()) {
            $payment->setStatus(self::STATUS_ERROR);
            $this->getHelper()->handleError(__('Error in voiding payment: ' . $result->getResponseText()), true);
        }
        
        return $this;
    }

    /**
     * If gateway actions are locked return true
     *
     * @param  \Magento\Payment\Model\InfoInterface $payment
     * @return bool
     */
    protected function isGatewayActionsLocked($payment)
    {
        return $payment->getAdditionalInformation(self::GATEWAY_ACTIONS_LOCKED_STATE_KEY);
    }

    /**
     * Operate with order using data from $_POST which came from moneris.com by Relay URL.
     *
     * @param array $responseData data from Authorize.net from $_POST
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException In case of validation error or order creation error
     */
    public function process($response)
    {
        $this->setResponseData($response);
        $response = $this->getResponse();
        $orderIncrementId = $response->getRealOrderId();
        $isError = false;
        if ($orderIncrementId) {
            /* @var $order \Magento\Sales\Model\Order */
            $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
            //check payment method
            $payment = $order->getPayment();
            if (!$payment || $payment->getMethod() != $this->getCode()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('This payment didn\'t work out because we can\'t find this order.')
                );
            }
            
            if ($order->getId()) {
                //operate with order
                $this->processOrder($order);
            } else {
                $isError = true;
            }
        } else {
            $isError = true;
        }
        
        if ($isError) {
            $responseText = $responseText && !$response->isApproved()
            ? $responseText
            : __('This payment didn\'t work out because we can\'t find this order.');
            throw new \Magento\Framework\Exception\LocalizedException($responseText);
        }
    }

    /**
     * Authorize order or authorize and capture it.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function processOrder(\Magento\Sales\Model\Order $order)
    {
        $response = $this->getResponse();
        //create transaction. need for void if amount will not match.
        $payment = $order->getPayment();
        $payment->getMethodInstance()->setIsInitializeNeeded(false);
        // Update payment details
        $payment->setCcType($response->getCart());
        $payment->setCcApproval($response->getBankApprovalCode());
        $payment->setIsTransactionClosed(0);
        $payment->setAdditionalInformation('receipt_id', $response->getBankTransactionId());
        $payment->place();
        // Update order status

        $order->setExtOrderId($response->getResponseOrderId());
        $order->save();

        // Send email confirmation
        $this->orderSender->send($order);

        try {
            $this->orderSender->send($order);
            $quote = $this->quoteRepository->get($order->getQuoteId())->setIsActive(false);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            // do not cancel order if we couldn't send email
            //@TODO: Log error here or do something for error handling
        }
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->getHelper()->log(__METHOD__ . __LINE__ . $paymentAction);
        $params = $this->request->getParams();

        $requestType = null;
        switch ($paymentAction) {
            case self::ACTION_AUTHORIZE:
                $requestType = self::REQUEST_TYPE_AUTH_ONLY;
                break;
            case self::ACTION_AUTHORIZE_CAPTURE:
                $requestType = $requestType ?: self::REQUEST_TYPE_AUTH_CAPTURE;
                $payment = $this->getInfoInstance();
                $order = $payment->getOrder();
                if ($params && $params['IDEBIT_TRACK2']) {
                    $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Interac\Completion');
                    $transaction->setPayment($payment)
                    ->setAmount($payment->getAmountOrdered())
                    ->setDataDebit($params['IDEBIT_TRACK2']);
                    $result = $transaction->post();
                    $this->getHelper()->log(__METHOD__ . __LINE__ . ' Result from Moneris purchase: ' . print_r($result, 1));
                    if ($result->getError()) {
                        $error = __("Error in processing payment: {$paymentType} " . $result->getResponseText());
                        $this->getHelper()->handleError($error, true);
                    }
                    
                    if (!$result->getSuccess()) {
                        $this->getHelper()->handleError(__('Error in processing payment: '
                            . $result->getResponseText()), true);
                    } else {
                        $payment->setTransactionId($result->getTxnNumber());
                        $payment->setLastTransactionId($result->getTxnNumber());
                        
                        if ($result->getReferenceNum()) {
                            $payment->setAdditionalInformation('bank_transaction_id', $result->getReferenceNum());
                        }
                        
                        $this->log(__METHOD__ . " success ! ");
                    }
                }

                $order->setCanSendNewEmailFlag(false);
                $payment->setBaseAmountAuthorized($order->getBaseTotalDue());
                $payment->setAmountAuthorized($order->getTotalDue());
                $payment->setAnetTransType($requestType);
                break;
            default:
                break;
        }
    }

    /**
     * Set initialization requirement state
     *
     * @param bool $isInitializeNeeded
     * @return void
     */
    public function setIsInitializeNeeded($isInitializeNeeded = true)
    {
        $this->_isInitializeNeeded = (bool)$isInitializeNeeded;
    }

    protected function _getFormattedAmount($amount)
    {
        return number_format($this->getAlternateCurrencyAdjustedAmount(
            $this->getHelper()->getObject('Magento\Framework\Locale\FormatInterface')->getNumber($amount)), 2, '.', '');
    }

    public function getAlternateCurrencyAdjustedAmount($amount, $payment = false)
    {
        if (!$payment) {
            $payment = $this->payment;
        }

        if ($this->getHelper()->isAlternateCurrency()) {
            $difference = $payment->getOrder()->getGrandTotal() / $payment->getOrder()->getBaseGrandTotal();
            return round($amount * $difference, 2);
        }
        
        return $amount;
    }
}
