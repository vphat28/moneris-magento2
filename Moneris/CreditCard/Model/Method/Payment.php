<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Method;

use Moneris\CreditCard\Helper\Data as chHelper;
use Moneris\CreditCard\Model\Transaction;
use Moneris\CreditCard\Model\AbstractModel as MonerisConstants;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestFactory;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Registry;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\TransparentInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Store\Model\ScopeInterface;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Payment extends AbstractPayment implements TransparentInterface, ConfigInterface
{
    const METHOD_CODE = 'chmoneriscc';
    const STATUS_ERROR = 'ERROD';

    const REDIRECT_PATH = 'moneriscc/payment/redirect';


    const AUTHORIZE = 'authorize';

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
    const GATEWAY_ACTIONS_LOCKED_STATE_KEY = 'is_gateway_actions_locked';

    const VBV_REQUIRED_DECLINE = "Credit card not allow";
    
    const CAVV_FIELD = 'cavv';
    const TRANS_STATUS = 'trans_status';

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = true;
    protected $amount;

    /** @var OrderModel\Payment */
    protected $payment;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapture = true;
    
    protected $_canCapturePartial           = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = false;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Authorizenet\Model\Directpost\Response
     */
    protected $response;

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
     * @var \Magento\Sales\Model\Order
     */
    private $orderModel;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var \Magento\Sales\Model\Order\Status
     */
    public $status;

    /*
     * @var string
     */
    const VISA_CODE = 'VI';

    /*
     * @var string
     */
    const MASTERCARD_CODE = 'MC';

    /**
     * @var array
     */
    protected $_vbvCcTypes = [
        self::VISA_CODE,
        self::MASTERCARD_CODE
    ];

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    private $responseFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $psrLogger;

    /**
     * @var bool
     */
    public $recurringOccurence;

    /**
     * Payment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param RequestFactory $requestFactory
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\DataObject\Factory $responseFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param chHelper $dataHelper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param OrderSender $orderSender
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param OrderModel $orderModel
     * @param \Magento\Sales\Model\Order\Status $status
     * @param null $resource
     * @param null $resourceCollection
     * @param array $data\
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        RequestFactory $requestFactory,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\DataObject\Factory $responseFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        chHelper $dataHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        OrderSender $orderSender,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Sales\Model\Order\Status $status,
        $resource = null,
        $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
                $registry,
                $requestFactory,
                $extensionFactory,
                $responseFactory,
                $customAttributeFactory,
                $paymentData,
                $scopeConfig,
                $logger,
                $moduleList,
                $localeDate,
                $dataHelper,
                $orderFactory,
                $invoiceService,
                $transaction,
                $storeManager,
                $quoteRepository,
                $orderSender,
                $transactionRepository,
                $resource,
                $resourceCollection,
                $data
        );
        $this->status = $status;
        $this->psrLogger = $context->getLogger();
        $this->orderModel = $orderModel;
        $this->responseFactory = $responseFactory;
    }

    /// LEGACY
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

    public function getIsVbvEnabled()
    {
        $payment = $this->payment;
        $methodCode = $payment->getMethodInstance()->getCode();
        if ($payment && $methodCode) {
            return $this->getHelper()->getConfigData("payment/".$methodCode."/vbv_enabled");
        }
        
        return $this->getHelper()->getIsVbvEnabled();
    }
    

    public function getIsVbvRequired()
    {
        $payment = $this->payment;
        $methodCode = $payment->getMethodInstance()->getCode();
        if ($payment && $methodCode) {
            return $this->getHelper()->getConfigData("payment/".$methodCode."/require_vbv");
        }
        
        return $this->getHelper()->getIsVbvRequired();
    }

    public function getIsVbvCompatible(\Magento\Framework\DataObject $payment)
    {
        $ccType = $payment->getCcType();
        return in_array($ccType, $this->_vbvCcTypes);
    }

    public function isPurchase()
    {
        $action = $this->getHelper()->getPaymentAction();
        return $action != self::AUTHORIZE && $action != self::CAPTURE;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->getConfigData('login') || !$this->getConfigData('password')) {
             return false;
        }
        
        return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currency)
    {
        return true;
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
        if (!$this->getHelper()->getIsFrontend()) {
            return Transaction::CRYPT_SEVEN;
        }
        
        if (!$this->getIsVbvEnabled() || !$this->getIsVbvCompatible($payment)) {
            return Transaction::CRYPT_SEVEN;
        }
        
        if ($this->getIsVbvEnabled()
            && $payment->getAdditionalInformation(self::TRANS_STATUS)
            && $payment->getAdditionalInformation(self::TRANS_STATUS) == MonerisConstants::CRYPT_RESP_U
        ) {
            return Transaction::CRYPT_SEVEN;
        }
        
        $this->getHelper()->log(__FILE__." ".__LINE__." Process  cryptType:");
        if ($payment->getAdditionalInformation('vault_id') && !$payment->getCcNumber()) {
            $this->getHelper()->getCheckoutSession()->setMonerisccVaultId($payment->getAdditionalInformation('vault_id'));
            $cryptType = $this->getHelper()->getObject('Moneris\CreditCard\Model\VaultPayment')
            ->setPayment($payment)
            ->setAmount($amount)
            ->fetchCryptType();
        } else {
            $this->log(__METHOD__ . 'vbv ');
            $cryptType = $this->getHelper()->getObject('Moneris\CreditCard\Model\Mpi\Txn')
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
                if ($payment->getCcType() == self::MASTERCARD_CODE && $this->getIsVbvRequired()) {
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

    protected function getUniqueOrderId($increment_id)
    {
        $tail = sprintf("%'920d", rand(999, 999999999 - strlen($increment_id)));
        return $increment_id . '-' . $tail;
    }

    /**
     * The user is redirected to the URL returned here on order placement
     * (see Mage_Checkout_Model_Type_Onepage).
     *
     * @return string url
     */
    public function getOrderPlaceRedirectUrl()
    {
        $this->psrLogger->info("order place redirect");
        $session = $this->getHelper()->getCheckoutSession();
        $this->psrLogger->info("order id = ".$session->getMonerisccOrderId());
        if ($session->getMonerisccOrderId()) {
            $this->psrLogger->info("call set Status");
            $this->setStatus($session->getMonerisccOrderId());
            return $this->getHelper()->getUrl(self::REDIRECT_PATH, ['_secure' => true]);
        }

        if ($session->getMonerisccCancelOrder()) {
            $session->setMonerisccCancelOrder(false);
            $this->getHelper()->handleError(__('Your card could not be VBV/3DS authenticated. Please use a VBV/3DS enrolled card.'));

            $url = $this->getHelper()->getPaymentFailedRedirectUrl();
            return $url;
        }

        $this->log(__METHOD__ . 'not redirecting');
        // TODO probably to return null for mage 2?
        return null;
    }

    public function setStatus($incrementId)
    {
        $this->psrLogger->info("set status");
        $order = $this->orderModel->loadByIncrementId($incrementId);
        $this->psrLogger->info("payment type = ".$order->getPayment()->getAdditionalInformation('payment_type'));
        
        if ($order->getPayment()->getAdditionalInformation('payment_type') && $order->getPayment()->getAdditionalInformation('payment_type') == self::AUTHORIZE) {
            $orderStatus = $this->status->loadDefaultByState('pending_payment')->getStatus();
            $order->setState("pending_payment")->setStatus($orderStatus);
            $this->psrLogger->info("set status pending");
        }

        $order->save();
    }
    
    /**
     * Completes payment with the data passed back from the MPI.
     * Throws an exception on failure.
     *
     * @param \Magento\Framework\DataObject $payment, $paRes, $md, $order
     */
    public function cavvContinue(\Magento\Framework\DataObject $payment, $paRes, $md, $order)
    {
        $this->payment = $payment;
        $amount = $payment->getAmountOrdered();
        
        $mpiResponse = $this->getHelper()->getObject('Moneris\CreditCard\Model\Mpi\Acs')
            ->setPaRes($paRes)
            ->setMd($md)
            ->setPayment($payment)
            ->post();

        $mpiSuccess = $mpiResponse->getMpiSuccess();
        $mpiMessage = $mpiResponse->getMpiMessage();
        $cavv = $mpiResponse->getMpiCavv();

        if ($mpiMessage != MonerisConstants::CRYPT_RESP_Y) {
            $this->getHelper()->getCheckoutSession()->setMonerisccCancelMessage($mpiMessage);
        }
        
        $this->log(__METHOD__ . __LINE__ . ' 3D fetchCryptType'.$mpiMessage);
        
        $this->payment->setAdditionalInformation(self::TRANS_STATUS, $mpiMessage);
        $mdArray = [];
        parse_str($md, $mdArray);

        if (isset($mdArray['pan'])) {
            $this->getHelper()->getCheckoutSession()->setMonerisccCcNumber($mdArray['pan']);
        }

        if ($mpiSuccess == MonerisConstants::RESPONSE_TRUE) {
            $this->log(__METHOD__ . 'mpi success');

            if ($this->isPurchase()) {
                $this->_cavvPurchase($payment, $md, $cavv);
            } else {
                $this->_cavvAuthorize($payment, $md, $cavv);
            }
            
            $this->payment->setAdditionalInformation(self::CAVV_FIELD, $cavv);
            //$order->sendNewOrderEmail();

            return $this;
        }

            //Authentication Not Available
        if ($mpiMessage == MonerisConstants::CRYPT_RESP_U) {
            $this->log(__METHOD__ . 'Authentication Not Available');
            if ($this->getIsVbvRequired()) {
                $message = __('Authentication Not Available. Please try another card or a different payment method.');
                $this->getHelper()->getCheckoutSession()->setMonerisccCancelMessage("Authentication Not Available");
                $this->getHelper()->handleError($message, true);
            }

            if ($this->isPurchase()) {
                $this->capture($payment, $amount);
            } else {
                $this->authorize($payment, $amount);
            }
            
            return $this;
        }

        //Authentication Failure
        if ($mpiMessage == MonerisConstants::CRYPT_RESP_N) {
            $this->log(__METHOD__ . 'Authentication Failure');

            if ($this->getIsVbvRequired()) {
                $message = __('Authentication Not Available. Please try another card or a different payment method.');
                $this->getHelper()->getCheckoutSession()->setMonerisccCancelMessage("Authentication Failure");
                $this->getHelper()->handleError($message, true);
            }

            $message = __('We were unable to verify your VBV / 3DS credentials. Please try again or use a different payment method.');
            $this->getHelper()->getCheckoutSession()->setMonerisccCancelMessage($message);
            $this->getHelper()->handleError($message, true);
        }

            // send regular auth unless vbv is required
        $this->log(__METHOD__ . 'no mpi');
        if ($this->getIsVbvRequired()) {
            $this->getHelper()->getCheckoutSession()->setMonerisccCancelMessage("3-D Secure authentication failed");
            $this->getHelper()->handleError(__('3-D Secure authentication failed. Please try again or use a different payment method.'), true);
        }

        if ($mpiSuccess == 'false') {
            $this->log(__METHOD__ . '3-D Secure authentication failed');
            $message = __('3-D Secure authentication failed. Please try again or use a different payment method.');
            $this->getHelper()->getCheckoutSession()->setMonerisccCancelMessage("3-D Secure authentication failed");
            $this->getHelper()->handleError($message, true);
        }

        if ($this->isPurchase()) {
            $this->capture($payment, $amount);
        } else {
            $this->authorize($payment, $amount);
        }

        return $this;
    }

    /**
     * Posts a cavv_preauth transaction.
     * Throws an exception on failure.
     *
     * @param \Magento\Framework\DataObject $payment, string $md, string $cavv
     */
    protected function _cavvAuthorize(\Magento\Framework\DataObject $payment, $md, $cavv)
    {
        $this->payment = $payment;
        $amount = $payment->getAmountOrdered();

        $mdArray = [];
        parse_str($md, $mdArray);

        if (!empty($mdArray) && isset($mdArray['pan'])) {
            $payment->setCcNumber($mdArray['pan']);
            $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Cavv\PreAuth')
            ->setPayment($payment)
            ->setAmount($amount)
            ->setCavv($cavv);
        } else {
            $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Vault\PreAuth')
            ->setPayment($payment)
            ->setAmount($amount)
            ->setCavv($cavv);
        }
        
        $result = $transaction->post();
        if ($result->getError()) {
            $this->getHelper()->log(__METHOD__ . __LINE__ . ' Error on CAVV authorizing: ' . print_r($result, 1));
            $error = __('There was an error authorizing your payment. Please use a different card or try a different payment method.');
            $this->getHelper()->handleError($error, true);
            $this->getHelper()->getCustomerSession()->setMonerCavvError(true);
        }

        if (!$result->getSuccess()) {
            $this->getHelper()->log(__FILE__." ".__LINE__." result " .print_r($result, 1));
            $this->getHelper()->handleError(__('The Transaction has been declined by your bank. Please use a different card or try a different payment method.'), true);
        }

        $this->_cavvSuccess($payment);

        return $this;
    }

    /**
     * Posts a cavv_purchase transaction.
     * Throws an exception on failure.
     *
     * @param \Magento\Framework\DataObject $payment, string $md, string $cavv
     */
    protected function _cavvPurchase(\Magento\Framework\DataObject $payment, $md, $cavv)
    {
        $this->payment = $payment;
        $session = $this->getHelper()->getCheckoutSession();
        $incrementId = $session->getMonerisccOrderId();
        $order = $this->orderModel->loadByIncrementId($incrementId);
        $amount = $order->getGrandTotal();

        $mdArray = [];
        parse_str($md, $mdArray);
        
        if (!empty($mdArray) && isset($mdArray['pan'])) {
            $payment->setCcNumber($mdArray['pan']);
            $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Cavv\Purchase')
            ->setPayment($payment)
            ->setAmount($amount)
            ->setCavv($cavv);
        } else {
            $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Vault\Purchase')
            ->setPayment($payment)
            ->setAmount($amount)
            ->setCavv($cavv);
        }
        
        $result = $transaction->post();
        if ($result->getError()) {
            $this->getHelper()->log(__METHOD__ . __LINE__ . ' Error on CAVV purchase: ' . print_r($result, 1));

            $error = __('There was an error processing your card. Please use a different card or try a different payment method.');
            $this->getHelper()->handleError($error, true);
        }

        if (!$result->getSuccess()) {
            $this->getHelper()->log(__METHOD__ . __LINE__ . 'Result 2: ' . print_r($result, 1));
            $this->getHelper()->handleError(__('The Transaction has been declined by your bank. Please use a different card or try a different payment method.'), true);
        }

        $this->_cavvSuccess($payment);

        // register the capture success
        $payment->setIsTransactionPending(false)
            ->registerCaptureNotification($amount);
        try {
            $payment->getOrder()->save();
        } catch (\Exception $e) {
            $this->getHelper()->critical($e);
        }

        return $this;
    }

    /**
     * Adds a VBV/3DS success message to $payment's order.
     *
     * @param \Magento\Framework\DataObject $payment
     * @return this
     */
    protected function _cavvSuccess(\Magento\Framework\DataObject $payment)
    {
        $order = $payment->getOrder();
        $order->setState(
            OrderModel::STATE_PROCESSING, OrderModel::STATE_PROCESSING,
            __('VBV / 3DS authentication completed successfully'),
            false
        );
        try {
            $order->save();
        } catch (\Exception $e) {
            $this->getHelper()->critical($e);
        }
        
        return $this;
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

        /** @var \Moneris\KountIntegration\DataProvider\KountDataProvider $kountDataProvider */
        $kountDataProvider = ObjectManager::getInstance()->get('\Moneris\KountIntegration\DataProvider\KountDataProvider');

        // Assign Data to Kount Integration
        if (!empty($additionalData)) {
            foreach (['cc_number', 'cc_cid', 'cc_exp_year', 'cc_exp_month', 'cc_type'] as $d) {
                if (isset($additionalData[$d])) {
                    $kountDataProvider->setAdditionalData($d, $additionalData[$d]);
                }
            }
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
                        'cc_type' => $vault->getCardType(),
                    ]
                );
            }
        }
        
        if ($data->hasData('recurringTerm') && $data->getData('recurringTerm') != '') {
            $info->setAdditionalInformation('recurringTerm', $data->getData('recurringTerm'));
        }

        if ($data->hasData('recurring') && $data->getData('recurring') != '') {
            $info->setAdditionalInformation('recurring', $data->getData('recurring'));
        }

        if ($data->hasData('save') && $data->getData('save') != '') {
            $info->setAdditionalInformation('save', $data->getData('save'));
        }
        
        return $this;
    }

    public function getConfigPaymentAction()
    {
        $action = parent::getConfigPaymentAction();

        /** @var Registry $register */
        $register = ObjectManager::getInstance()->get(Registry::class);
        $byPass = $register->registry('by_pass_authorize_payment');

        if ($byPass) {
            $action = self::ACTION_AUTHORIZE_CAPTURE;
        }

        return $action;
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
        $this->psrLogger->info("auth only");
        $this->payment = $payment;
        $this->amount = $amount;
        $this->psrLogger->info("order id = ".$this->payment->getOrder()->getStatus());

        /** @var Registry $register */
        $register = ObjectManager::getInstance()->get(Registry::class);
        $byPass = $register->registry('by_pass_authorize_payment');

        if ($byPass) {
            $this->recurringOccurence = true;
            $this->payment->setLastTransId($this->payment->getAdditionalInformation('moneris_receipt_id'));
            $this->payment->setAdditionalInformation('receipt_id', $this->payment->getAdditionalInformation('moneris_receipt_id'));
            $this->payment->setTransactionId($this->payment->getAdditionalInformation('moneris_trans_id'));
            return $this;
        }

        return $this->processTransaction(self::AUTHORIZE);
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
        $this->psrLogger->info("capture");
        $this->payment = $payment;
        $this->amount = $amount;

        /** @var Registry $register */
        $register = ObjectManager::getInstance()->get(Registry::class);
        $byPass = $register->registry('by_pass_authorize_payment');

        if ($byPass) {
            $this->recurringOccurence = true;
            $this->payment->setLastTransId($this->payment->getAdditionalInformation('moneris_receipt_id'));
            $this->payment->setAdditionalInformation('receipt_id', $this->payment->getAdditionalInformation('moneris_receipt_id'));
            $this->payment->setCcTransId($this->payment->getAdditionalInformation('moneris_trans_id'));
            $this->payment->setTransactionId($this->payment->getAdditionalInformation('moneris_trans_id'));
            return $this;
        }

        return $this->processTransaction(self::CAPTURE);
    }

    public function processTransaction($paymentType = self::AUTHORIZE)
    {
        $this->psrLogger->info("payment action = ".$this->getConfigData('payment_action'));
        $this->payment->setAdditionalInformation('payment_type', $this->getConfigData('payment_action'));

        $this->getHelper()->getCheckoutSession()->setMonerisccOrderId(false);

        // Reset the settings
        $this->getHelper()->getCheckoutSession()->setMonerisccCancelOrder(false);

        $this->amount = $this->_getFormattedAmount($this->amount);

        $this->log("CcTransId: ". $this->payment->getCcTransId() .
            " LastTransId: " . $this->payment->getLastTransId() .
            " paymentType: $paymentType " .
            " ParentTransId: " . $this->payment->getParentTransactionId() .
            " Amount: {$this->amount}  " . $this->payment->getOrder()->getBaseGrandTotal());

        if ($this->amount < 0) {
            $this->getHelper()->handleError(__("Invalid amount to process: [{$this->amount}]"), true);
        }

        //Save credit card to data.
        $isSave = $this->payment->getAdditionalInformation('save');
        if ($isSave) {
            $this->log(__METHOD__ . " save data customer ! ");
            $this->saveVaultToCustomerData($this->payment);
        }

        $isRecurring = $this->payment->getAdditionalInformation('recurring');
        $isCompletion = $paymentType != self::AUTHORIZE && $this->payment->getCcTransId();

        if ($isRecurring && !$isCompletion && empty($this->payment->getAdditionalInformation('vault_id'))) {
            $vault = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Vault')
                ->setPayment($this->payment)
                ->setCryptType(7);
            $result = $vault->post(null, false);

            if ($result !== null) {
                $dataKey = $result->getDataKey();
                $this->payment->setAdditionalInformation('data_key', $dataKey);
            }
        }

        $cryptType = false;
        
        if (!$isCompletion) {
            $cryptType = $this->fetchCryptType($this->payment, $this->amount);
        }

        $this->getHelper()->log(__FILE__." ".__LINE__." Process  cryptType: ".$cryptType);

        $this->psrLogger->info("crypt type = ".$cryptType);
        if (!$isCompletion && !$cryptType) {
            $this->getHelper()->log(__FILE__." ".__LINE__." Reset CVD result");
            $this->getHelper()->getCheckoutSession()->setMonerisCavvCvdResult(false);

            $this->getHelper()->handleError(__('Only VBV / 3DS enrolled cards are accepted. Please try another card or a different payment method.'));
            $this->payment->setIsTransactionPending(true);
            return $this;
        }

        if ($this->getIsVbvRequired() && ($cryptType == 6 || $cryptType == 7)) {
            $this->getHelper()->handleError(__('Only VBV / 3DS enrolled cards are accepted. Please try another card or a different payment method.'));
            return $this;
        }

        if ($paymentType == self::AUTHORIZE) {
            $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\PreAuth')
                ->setPayment($this->payment)
                ->setAmount($this->amount)
                ->setCryptType($cryptType);
        } else {
            if ($isCompletion) {
                if ($this->payment->getOrder()->getData('total_paid') > 0) {
                    $isReAuth = true;
                    $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\ReAuth');
                } else {
                    $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Completion');
                }
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

        $this->postTransaction($transaction, $paymentType);

        if (!empty($isReAuth)) {
            $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Completion');
            $transaction->setPayment($this->payment)
                ->setAmount($this->amount);

            $cavv = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'cavv');
            if ($cavv) {
                $transaction->setCavv($cavv);
            }
            $this->postTransaction($transaction, $paymentType);
        }

        return $this;
    }

    private function postTransaction($transaction, $paymentType)
    {
        $result = $transaction->post();

        if ($result->getError()) {
            $this->getHelper()->log(__METHOD__ . __LINE__ . ' Error on CAVV purchase: ' . print_r($result, 1));
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

        if (self::AUTHORIZE != $paymentType) {
            $this->payment->setIsTransactionClosed(0)
                ->setTransactionAdditionalInfo(
                    self::REAL_TRANSACTION_ID_KEY,
                    $this->payment->getLastTransId()
                );
        }
    }

    /**
     * Save a credit Card to Customer Vault.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    private function saveVaultToCustomerData(\Magento\Payment\Model\InfoInterface $payment)
    {
        if (!$payment) {
            return [];
        }
        
        $vault = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Vault')
        ->setPayment($payment)
        ->setCryptType(7);
        $result = $vault->post();
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
     * Return response.
     *
     * @return \Magento\Authorizenet\Model\Directpost\Response
     */
    public function getResponse()
    {
        return $this->response;
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
        $requestType = null;
        switch ($paymentAction) {
            case self::ACTION_AUTHORIZE:
                $requestType = self::REQUEST_TYPE_AUTH_ONLY;
            //intentional
            case self::ACTION_AUTHORIZE_CAPTURE:
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
     * Generate request object and fill its fields from Quote or Order object
     *
     * @param \Magento\Sales\Model\Order $order Quote or order object.
     * @return \Magento\Authorizenet\Model\Directpost\Request
     */
    public function generateRequestFromOrder(\Magento\Sales\Model\Order $order)
    {
        $request = $this->requestFactory->create()
            ->setConstantData($this)
            ->setDataFromOrder($order, $this)
            ->signRequestData();

        $this->_debug(['request' => $request->getData()]);

        return $request;
    }

    /**
     * Fill response with data.
     *
     * @param array $postData
     * @return $this
     */
    public function setResponseData(array $postData)
    {
        $this->getResponse()->setData($postData);
        return $this;
    }

    /**
     * Validate response data. Needed in controllers.
     *
     * @return bool true in case of validation success.
     * @throws \Magento\Framework\Exception\LocalizedException In case of validation error
     */
    public function validateResponse()
    {
        $response = $this->getResponse();
        //md5 check
        if (!$this->getConfigData('trans_md5')
            || !$this->getConfigData('login')
            || !$response->isValidHash($this->getConfigData('trans_md5'), $this->getConfigData('login'))
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The transaction was declined because the response hash validation failed.')
            );
        }

        return true;
    }

    /**
     * Operate with order using data from $_POST which came from authorize.net by Relay URL.
     *
     * @param array $responseData data from Authorize.net from $_POST
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException In case of validation error or order creation error
     */
    public function process(array $responseData)
    {
        $this->_debug(['response' => $responseData]);

        $this->setResponseData($responseData);

        $this->validateResponse();

        $response = $this->getResponse();
        $orderIncrementId = $response->getXInvoiceNum();
        $responseText = $this->dataHelper->wrapGatewayError($response->getXResponseReasonText());
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
     * Fill payment with credit card data from response from Authorize.net.
     *
     * @param \Magento\Framework\DataObject $payment
     * @return void
     */
    protected function fillPaymentByResponse(\Magento\Framework\DataObject $payment)
    {
        $response = $this->getResponse();
        $payment->setTransactionId($response->getXTransId())
            ->setParentTransactionId(null)
            ->setIsTransactionClosed(0)
            ->setTransactionAdditionalInfo('real_transaction_id', $response->getXTransId());

        if ($response->getXMethod() == self::REQUEST_METHOD_CC) {
            $payment->setCcAvsStatus($response->getXAvsCode())
                ->setCcLast4($payment->encrypt(substr($response->getXAccountNumber(), -4)));
        }

        if ($response->getXResponseCode() == self::RESPONSE_CODE_HELD) {
            $payment->setIsTransactionPending(true)
                ->setIsFraudDetected(true);
        }
    }

    /**
     * Check response code came from Authorize.net.
     *
     * @return true in case of Approved response
     * @throws \Magento\Framework\Exception\LocalizedException In case of Declined or Error response from Authorize.net
     */
    public function checkResponseCode()
    {
        switch ($this->getResponse()->getXResponseCode()) {
            case self::RESPONSE_CODE_APPROVED:
            case self::RESPONSE_CODE_HELD:
                return true;
            case self::RESPONSE_CODE_DECLINED:
            case self::RESPONSE_CODE_ERROR:
                throw new \Magento\Framework\Exception\LocalizedException(
                    $this->dataHelper->wrapGatewayError($this->getResponse()->getXResponseReasonText())
                );
            default:
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('There was a payment authorization error.')
                );
        }
    }

    /**
     * Check transaction id came from Authorize.net
     *
     * @return true in case of right transaction id
     * @throws \Magento\Framework\Exception\LocalizedException In case of bad transaction id.
     */
    public function checkTransId()
    {
        if (!$this->getResponse()->getXTransId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please enter a transaction ID to authorize this payment.')
            );
        }
        
        return true;
    }

    /**
     * Compare amount with amount from the response from Authorize.net.
     *
     * @param float $amount
     * @return bool
     */
    protected function matchAmount($amount)
    {
        return sprintf('%.2F', $amount) == sprintf('%.2F', $this->getResponse()->getXAmount());
    }

    /**
     * Operate with order using information from Authorize.net.
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
        try {
            $this->checkResponseCode();
            $this->checkTransId();
        } catch (\Exception $e) {
            //decline the order (in case of wrong response code) but don't return money to customer.
            $message = $e->getMessage();
            $this->declineOrder($order, $message, false);
            throw $e;
        }

        $response = $this->getResponse();

        //create transaction. need for void if amount will not match.
        $payment = $order->getPayment();
        $this->fillPaymentByResponse($payment);
        $payment->getMethodInstance()->setIsInitializeNeeded(false);
        $payment->getMethodInstance()->setResponseData($response->getData());
        $this->processPaymentFraudStatus($payment);
        $payment->place();
        $this->addStatusComment($payment);
        $order->save();

        //match amounts. should be equals for authorization.
        //decline the order if amount does not match.
        if (!$this->matchAmount($payment->getBaseAmountAuthorized())) {
            $message = __(
                'Something went wrong: the paid amount doesn\'t match the order amount.'
                . ' Please correct this and try again.'
            );
            $this->declineOrder($order, $message, true);
            throw new \Magento\Framework\Exception\LocalizedException($message);
        }

        try {
            if (!$response->hasOrderSendConfirmation() || $response->getOrderSendConfirmation()) {
                $this->orderSender->send($order);
            }

            $quote = $this->quoteRepository->get($order->getQuoteId())->setIsActive(false);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->getHelper()->log(__METHOD__ . __LINE__);
        }
    }

    /**
     * Process fraud status
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return $this
     */
    protected function processPaymentFraudStatus(\Magento\Sales\Model\Order\Payment $payment)
    {
        try {
            $fraudDetailsResponse = $payment->getMethodInstance()
                ->fetchTransactionFraudDetails($this->getResponse()->getXTransId());
            $fraudData = $fraudDetailsResponse->getData();

            if (empty($fraudData)) {
                $payment->setIsFraudDetected(false);
                return $this;
            }

            $payment->setIsFraudDetected(true);
            $payment->setAdditionalInformation('fraud_details', $fraudData);
        } catch (\Exception $e) {
            $this->getHelper()->log(__METHOD__ . __LINE__);
        }

        return $this;
    }

    /**
     * Add status comment
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return $this
     */
    protected function addStatusComment(\Magento\Sales\Model\Order\Payment $payment)
    {
        try {
            $transactionId = $this->getResponse()->getXTransId();
            $data = $payment->getMethodInstance()->getTransactionDetails($transactionId);
            $transactionStatus = (string)$data->transaction->transactionStatus;
            $fdsFilterAction = (string)$data->transaction->FDSFilterAction;

            if ($payment->getIsTransactionPending()) {
                $message = 'Amount of %1 is pending approval on the gateway.<br/>'
                    . 'Transaction "%2" status is "%3".<br/>'
                    . 'Transaction FDS Filter Action is "%4"';
                $message = __(
                    $message,
                    $payment->getOrder()->getBaseCurrency()->formatTxt($this->getResponse()->getXAmount()),
                    $transactionId,
                    $this->dataHelper->getTransactionStatusLabel($transactionStatus),
                    $this->dataHelper->getFdsFilterActionLabel($fdsFilterAction)
                );
                $payment->getOrder()->addStatusHistoryComment($message);
            }
        } catch (\Exception $e) {
            $this->getHelper()->log(__METHOD__ . __LINE__);
        }
        
        return $this;
    }

    /**
     * Register order cancellation. Return money to customer if needed.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $message
     * @param bool $voidPayment
     * @return void
     */
    protected function declineOrder(\Magento\Sales\Model\Order $order, $message = '', $voidPayment = true)
    {
        try {
            $response = $this->getResponse();
            if ($voidPayment && $response->getXTransId() && strtoupper($response->getXType()) == self::REQUEST_TYPE_AUTH_ONLY) {
                $order->getPayment()->setTransactionId(null)->setParentTransactionId($response->getXTransId())->void();
            }

            $order->registerCancellation($message)->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * Return additional information`s transaction_id value of parent transaction model
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return string
     */
    protected function getRealParentTransactionId($payment)
    {
        $transaction = $this->transactionRepository->getByTransactionId(
            $payment->getParentTransactionId(),
            $payment->getId(),
            $payment->getOrder()->getId()
        );
        return $transaction->getAdditionalInformation(self::REAL_TRANSACTION_ID_KEY);
    }

    /**
     * {inheritdoc}
     */
    public function getConfigInterface()
    {
        return $this;
    }

    /**
     * Getter for specified value according to set payment method code
     *
     * @param mixed $key
     * @param null $storeId
     * @return mixed
     */
    public function getValue($key, $storeId = null)
    {
        return $this->getConfigData($key, $storeId);
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

    /**
     * Get whether it is possible to capture
     *
     * @return bool
     */
    public function canCapture()
    {
        return !$this->isGatewayActionsLocked($this->getInfoInstance());
    }

    /**
     * Fetch transaction details info
     *
     * Update transaction info if there is one placing transaction only
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
    {
        $transaction = $this->transactionRepository->getByTransactionId(
            $transactionId,
            $payment->getId(),
            $payment->getOrder()->getId()
        );

        $response = $this->getTransactionResponse($transactionId);
        if ($response->getXResponseCode() == self::RESPONSE_CODE_APPROVED) {
            if ($response->getTransactionStatus() == 'voided') {
                $payment->setIsTransactionDenied(true);
                $payment->setIsTransactionClosed(true);
                $transaction->close();
            } else {
                $transaction->setAdditionalInformation(self::TRANSACTION_FRAUD_STATE_KEY, false);
                $payment->setIsTransactionApproved(true);
            }
        } elseif ($response->getXResponseReasonCode() == self::RESPONSE_REASON_CODE_PENDING_REVIEW_DECLINED) {
            $payment->setIsTransactionDenied(true);
        }
        
        $this->addStatusCommentOnUpdate($payment, $response, $transactionId);
        return [];
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param \Magento\Framework\DataObject $response
     * @param string $transactionId
     * @return $this
     */
    protected function addStatusCommentOnUpdate(
        \Magento\Sales\Model\Order\Payment $payment,
        \Magento\Framework\DataObject $response,
        $transactionId
    ) {
        if ($payment->getIsTransactionApproved()) {
            $message = __(
                'Transaction %1 has been approved. Amount %2. Transaction status is "%3"',
                $transactionId,
                $payment->getOrder()->getBaseCurrency()->formatTxt($payment->getAmountAuthorized()),
                $this->dataHelper->getTransactionStatusLabel($response->getTransactionStatus())
            );
            $payment->getOrder()->addStatusHistoryComment($message);
        } elseif ($payment->getIsTransactionDenied()) {
            $message = __(
                'Transaction %1 has been voided/declined. Transaction status is "%2". Amount %3.',
                $transactionId,
                $this->dataHelper->getTransactionStatusLabel($response->getTransactionStatus()),
                $payment->getOrder()->getBaseCurrency()->formatTxt($payment->getAmountAuthorized())
            );
            $payment->getOrder()->addStatusHistoryComment($message);
        }
        
        return $this;
    }

    /**
     * This function returns full transaction details for a specified transaction ID.
     *
     * @param string $transactionId
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     * @link http://www.authorize.net/support/ReportingGuide_XML.pdf
     * @link http://developer.authorize.net/api/transaction_details/
     */
    protected function getTransactionResponse($transactionId)
    {
        $responseXmlDocument = $this->transactionService->getTransactionDetails($this, $transactionId);

        $response = $this->responseFactory->create();
        $response->setXResponseCode((string)$responseXmlDocument->transaction->responseCode)
            ->setXResponseReasonCode((string)$responseXmlDocument->transaction->responseReasonCode)
            ->setTransactionStatus((string)$responseXmlDocument->transaction->transactionStatus);

        return $response;
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
}
