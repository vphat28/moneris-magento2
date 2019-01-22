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
class Hosted extends AbstractPayment implements TransparentInterface, ConfigInterface
{
    const METHOD_CODE = 'chmonerisredirect';
    const GATEWAY_ACTIONS_LOCKED_STATE_KEY = 'is_gateway_actions_locked';
    const CAPTURE = 'capture';
    const PAYMENT_ACTION_CAPTURE = 'authorize_capture';
    const PAYMENT_ACTION_AUTH = 'authorize';
    const PAYMENT_PURCHASE = 'authorize';
    const  REQUEST_METHOD_CC = 'CC' ;
    const  REQUEST_METHOD_ECHECK = 'ECHECK';
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
    /**
     * Payment Method feature
     * LEGACY
     * @var bool
     */
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
        if (!$this->getConfigData('ps_store_id')
            || !$this->getConfigData('hpp_key')
            || !$this->getHelper()->getIsFrontend()
        ) {
            return false;
        }

            return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currency)
    {
        return true;
    }

    /// LEGACY
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
     * Update the CC info during the checkout process.
     *
     * @param \Magento\Framework\DataObject|mixed $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        // From Magento 2.0.7 onwards, the data is passed in a different property
        $additionalData = $data->getAdditionalData();
        if (is_array($additionalData)) {
            $data->setData(array_merge($data->getData(), $additionalData));
        }

        $info = $this->getInfoInstance();

        if ($data->hasData('vault_id')
            && $data->getData('vault_id') != ''
            ) {
            $info->setAdditionalInformation('vault_id', $data->getData('vault_id'));
            $vault = $this->getHelper()->getObject('Moneris\CreditCard\Model\Vault')->load($data->getData('vault_id'));
            if ($vault && $vault->getCardType()) {
                $info->addData(
                    [
                        'cc_type' => $vault->getCardType()
                    ]
                );
            }
        }

        return $this;
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
        $cryptType = false;

        if ($this->payment->getAdditionalInformation('vault_id')) {
            if (!$isCompletion) {
                $cryptType = $this->fetchCryptType($this->payment, $this->amount);
            }
            
            $this->log(__FILE__." ".__LINE__." Process  cryptType:".$cryptType);
            if (!$isCompletion && !$cryptType) {
                $this->log(__FILE__." ".__LINE__." Reset CVD result");
                $this->getHelper()->getCheckoutSession()->setMonerisCavvCvdResult(false);
                $this->getHelper()->handleError(__('Only VBV / 3DS enrolled cards are accepted. Please try another card or a different payment method.'));
                $this->payment->setIsTransactionPending(true);
                return $this;
            }

            if ($paymentType == self::PAYMENT_ACTION_AUTH) {
                $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\PreAuth')
                ->setPayment($this->payment)
                ->setAmount($this->amount)
                ->setCryptType($cryptType);
            } else {
                if ($isCompletion) {
                    $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Completion')
                    ->setCryptType(Transaction::CRYPT_SEVEN);
                } else {
                    $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Purchase')
                    ->setCryptType($cryptType);
                }

                $transaction->setPayment($this->payment)
                ->setAmount($this->amount);

                $cavv = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'cavv');
                if ($cavv) {
                    $transaction->setCavv($cavv);
                }
            }

            $result = $transaction->post();

            if ($result->getError()) {
                $this->log(__METHOD__ . __LINE__ . ' Error on CAVV purchase: ' . print_r($result, 1));
                $error = __("Error in processing payment: {$paymentType} " . $result->getMessage());
                $this->getHelper()->handleError($error, true);
            }

            if (!$result->getSuccess()) {
                $this->getHelper()->handleError(__('Error in processing payment: '. $result->getMessage()), true);
            } else {
                $this->log(__METHOD__ . " success ! ");
            }
            
            //Save moneris last_trans_id into order payment for capture purpose.
            $this->payment->setAdditionalInformation('receipt_id', $result->getLastTransId());
        } else {
            if ($paymentType == self::PAYMENT_ACTION_AUTH) {
                $this->log(__METHOD__ . " PAYMENT_ACTION_AUTH ! ");
            } else {
                if ($isCompletion && ($this->payment->getTransactionId() !== $this->payment->getLastTransId())) {
                    $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Completion');
                    $transaction->setPayment($this->payment)->setAmount($this->amount);
                    $result = $transaction->post();
                    if ($result->getError()) {
                        $error = __("Error in processing payment: {$paymentType} " . $result->getMessage());
                        $this->getHelper()->handleError($error, true);
                    }

                    if (!$result->getSuccess()) {
                        $this->getHelper()->handleError(__('Error in processing payment: '. $result->getMessage()), true);
                    } else {
                        $this->log(__METHOD__ . " success ! ");
                    }
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

        $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Refund')
        ->setPayment($payment)
        ->setAmount($amount);
        $result = $transaction->post();
        if (!$result->getSuccess()) {
            $this->getHelper()->handleError(__('Error in refunding payment: ' . $result->getMessage()), true);
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
            $this->getHelper()->handleError(__('Error in voiding payment: ' . $result->getMessage()), true);
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
            $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
            $payment = $order->getPayment();
            if (!$payment || $payment->getMethod() != $this->getCode()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('This payment didn\'t work out because we can\'t find this order.')
                );
            }

            if ($order->getId()) {
                $this->processOrder($order);
            } else {
                $isError = true;
            }
        } else {
            $isError = true;
        }

        if ($isError) {
            $responseText = $responseText && !$response->isApproved()
            ? $responseText : __('This payment didn\'t work out because we can\'t find this order.');
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
        $payment->setTransactionId($response->getBankTransactionId());
        $payment->setLastTransactionId($response->getBankTransactionId());
        $payment->setCcTransId($response->getBankTransactionId());
        $payment->setLastTransId($response->getBankTransactionId());
        $payment->setCcType($response->getCart());
        $payment->setCcApproval($response->getBankApprovalCode());
        $payment->setIsTransactionClosed(0);
        $payment->setAdditionalInformation('trans_name', $response->getTransName());
        $payment->setAdditionalInformation('receipt_id', $response->getResponseOrderId());
        $payment->place();

        //// create Invoice
        $request = $this->requestFactory->create();
        $params = $request->getParams();
        if ($params && (strpos($params['trans_name'], 'idebit_purchase') !==false) && $order->canInvoice()) {
            $this->log(__METHOD__ . __LINE__. $params['trans_name']);
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->_transaction->addObject(
                $invoice
                )->addObject(
                    $invoice->getOrder()
            );
            $transactionSave->save();
        }

        $order->setExtOrderId($response->getResponseOrderId());
        $order->save();

        // Send email confirmation
        $this->orderSender->send($order);

        try {
            $this->orderSender->send($order);
            $quote = $this->quoteRepository->get($order->getQuoteId())->setIsActive(false);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->log(__METHOD__ . __LINE__);
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
        $payment = $this->payment;
        $requestType = null;

        switch ($paymentAction) {
            case self::ACTION_AUTHORIZE:
                $requestType = self::REQUEST_TYPE_AUTH_ONLY;
                break;
                //intentional
            case self::ACTION_AUTHORIZE_CAPTURE:
                $this->log(__METHOD__ . __LINE__);
                $requestType = $requestType ?: self::REQUEST_TYPE_AUTH_CAPTURE;
                $payment = $this->getInfoInstance();
                $order = $payment->getOrder();
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
        return number_format($this->getAlternateCurrencyAdjustedAmount($this->getHelper()->getObject('Magento\Framework\Locale\FormatInterface')->getNumber($amount)), 2, '.', '');
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

    /**
     * Gets the crypt type for $payment via Moneris MPI call.
     * Checks if VBV is enabled in the config first.
     * Returns false if the payment needs to be VBV/3DS authenticated.
     *
     * @param \Magento\Framework\DataObject $payment, float $amount
     * @return string $cryptType if txn should proceed immediately, else false
     */
    public function fetchCryptType(\Magento\Framework\DataObject $payment, $amount)
    {
        if ($this->getIsVbvEnabled()
            && $payment->getAdditionalInformation(self::TRANS_STATUS)
            && $payment->getAdditionalInformation(self::TRANS_STATUS) == MonerisConstants::CRYPT_RESP_U
        ) {
            return Transaction::CRYPT_SEVEN;
        }
        
        $this->log(__FILE__." ".__LINE__." Process  cryptType:");
        $cryptType = Transaction::CRYPT_SEVEN;
        if ($payment->getAdditionalInformation('vault_id')) {
            $this->getHelper()->getCheckoutSession()->setMonerisccVaultId($payment->getAdditionalInformation('vault_id'));
            $cryptType = $this->getHelper()->getObject('Moneris\CreditCard\Model\VaultPayment')
            ->setPayment($payment)
            ->setAmount($amount)
            ->fetchCryptType();
        }

        switch ($cryptType) {
            case Transaction::CRYPT_SEVEN:
                // crypt 7 -> no liability shift
                if ($this->getIsVbvRequired()) {
                    $this->_markOrderForCancellation($payment);
                    return false;
                }
                break;
            case Transaction::CRYPT_SIX:
                // crypt 6 for mastercard -> no liability shift
                if ($this->getIsVbvRequired()) {
                    $this->_markOrderForCancellation($payment);
                    return false;
                }
                break;
            case Transaction::CRYPT_FIVE:
                // that's it for now; proceed to PaRes
                return false;
                break;
            default:
                // unexpected; abort
                $this->_markOrderForCancellation($payment);
                return false;
        }
        
        return $cryptType;
    }

    public function getIsVbvEnabled()
    {
        return $this->getHelper()->getConfigData("payment/".$this->_code."/vbv_enabled");
    }

    public function getIsVbvRequired()
    {
        return $this->getHelper()->getConfigData("payment/".$this->_code."/require_vbv");
    }

    /**
     * Sets a flag in the session so the observer can cancel the order.
     *
     * @param \Magento\Framework\DataObject $payment
     * @return this
     */
    protected function _markOrderForCancellation($payment)
    {
        $this->log(__METHOD__ . 'vbv was not successful and is required; marking order for cancellation via observer');
        if ($payment && $payment->getOrder()) {
            $this->declineOrder($payment->getOrder(), __(SELF::VBV_REQUIRED_DECLINE), true);
        } else {
            $this->getHelper()->getCheckoutSession()->setMonerisccCancelOrder(true);
        }
        
        return $this;
    }
}
