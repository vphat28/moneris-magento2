<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Method;

use Moneris\CreditCard\Helper\Data as chHelper;
use Moneris\CreditCard\Model\Transaction;
use Moneris\CreditCard\Model\AbstractModel as MonerisConstants;
use Magento\Framework\App\RequestFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClientFactory;
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
abstract class AbstractPayment extends \Magento\Payment\Model\Method\Cc
{
    const METHOD_CODE = 'chmoneriscc';

    const REDIRECT_PATH = 'moneriscc/payment/redirect';


    const REAL_TRANSACTION_ID_KEY = 'real_transaction_id';
    const AUTRHORIZE = 'authorize';

    const CAPTURE = 'capture';
    
    const CAVV_FIELD = 'cavv';
    const TRANS_STATUS = 'trans_status';
    /**
     * @var string
     */
    protected $_formBlockType = 'Magento\Payment\Block\Transparent\Info';

    /**
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info';
    protected $_canUseInternal = false;
    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = true;
    protected $amount;
    protected $payment;

    /**
     * @var RequestFactory
     */
    public $requestFactory;

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
    protected $_isInitializeNeeded = true;

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
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

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
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;
    
    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    private $responseFactory;

    /**
     * AbstractPayment constructor.
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
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
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
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->orderFactory = $orderFactory;
        $this->requestFactory = $requestFactory;
        $this->storeManager = $storeManager;
        $this->quoteRepository = $quoteRepository;
        $this->response = $responseFactory->create();
        $this->orderSender = $orderSender;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->chHelper = $dataHelper;
        $this->transactionRepository = $transactionRepository;
        $this->_code = static::METHOD_CODE;
        $this->responseFactory = $responseFactory;

        if ($this->getHelper()->isCCTestMode()) {
            if (!defined('MONERIS_TEST')) {
                define('MONERIS_TEST', 1);
            }
        }
        
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return $this->_scopeConfig->isSetFlag('payment/moneris/' . $this->_code . '/active', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param string $field
     * @param null $store
     * @return mixed
     */
    public function getConfigData($field, $store = null)
    {
        $data = $this->_scopeConfig->getValue('payment/moneris/' . $this->_code . '/' . $field, ScopeInterface::SCOPE_STORE, $store);

        if (empty($data)) {
            $data = $this->_scopeConfig->getValue('payment/' . $this->_code . '/' . $field);
        }

        return $data;
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
        return $this->getHelper()->getIsVbvEnabled();
    }

    public function getIsVbvRequired()
    {
        return $this->getHelper()->getIsVbvRequired();
    }

    public function getIsVbvCompatible(\Magento\Framework\DataObject $payment)
    {
        $ccType = $payment->getCcType();
        return in_array($ccType, $this->_vbvCcTypes);
    }

    public function getAlternateCurrencyAdjustedAmount($amount, $payment = false)
    {
        if (!$payment) {
            $payment = $this->_payment;
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
        if (!$this->getIsVbvEnabled() || !$this->getIsVbvCompatible($payment)) {
            return Transaction::CRYPT_SEVEN;
        }

        $cryptType = $this->getHelper()->getObject('Moneris\CreditCard\Model\Mpi\Txn')
            ->setPayment($payment)
            ->setAmount($amount)
            ->fetchCryptType();

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
     * @return $this
     */
    protected function _markOrderForCancellation($payment)
    {
        $this->log(__METHOD__ . 'vbv was not successful and is required; marking order for cancellation via observer');
        if ($payment && $payment->getOrder()) {
            // TOOD we probably have to test if this is auth or not. auth may not void
            $this->declineOrder($payment->getOrder(), __("VBV_REQUIRED_DECLINE"), true);
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
        $session = $this->getHelper()->getCheckoutSession();
        if ($session->getMonerisccOrderId()) {
            $this->log(__METHOD__ . 'redirecting!');
            return $this->getHelper()->getUrl(self::REDIRECT_PATH, ['_secure' => true]);
        }

        if ($session->getMonerisccCancelOrder()) {
            $this->log(__METHOD__ . 'order canceled; redirecting to repopulated cart');

            $session->setMonerisccCancelOrder(false);
            $this->getHelper()->handleError(
                __('Your card could not be VBV/3DS authenticated. Please use a VBV/3DS enrolled card.')
            );

            $url = $this->getHelper()->getPaymentFailedRedirectUrl();
            return $url;
        }

        $this->log(__METHOD__ . 'not redirecting');
        return '';
    }

    /**
     * Completes payment with the data passed back from the MPI.
     *
     * @param \Magento\Framework\DataObject $payment
     * @param $paRes
     * @param $md
     * @param $order
     * @return $this
     */
    public function cavvContinue(\Magento\Framework\DataObject $payment, $paRes, $md, $order)
    {
        $mpiResponse = $this->getHelper()->getObject('Moneris\CreditCard\Model\Method\Mpi\Acs')
            ->setPaRes($paRes)
            ->setMd($md)
            ->post();

        $this->getHelper()->log(
            __METHOD__ . __LINE__ . " cavvContinue ".print_r($mpiResponse, true)
        );
        $mpiSuccess = $mpiResponse->getMpiSuccess();
        $mpiMessage = $mpiResponse->getMpiMessage();
        $cavv = $mpiResponse->getMpiCavv();

        $mdArray = [];
        parse_str($md, $mdArray);

        if ($mpiSuccess == MonerisConstants::RESPONSE_TRUE) {
            $this->log(__METHOD__ . 'mpi success');

            if ($this->isPurchase()) {
                $this->_cavvPurchase($payment, $md, $cavv);
            } else {
                $this->_cavvAuthorize($payment, $md, $cavv);
            }

            return $this;
        }

        if ($mpiMessage == MonerisConstants::CRYPT_RESP_N) {
            $this->log(__METHOD__ . 'mpi failure');
            $this->getHelper()->handleError(
                __(
                    'We were unable to verify your VBV / 3DS credentials. 
                    Please try again or use a different payment method.'
                ),
                true
            );
        }

        // send regular auth unless vbv is required
        $this->log(__METHOD__ . 'no mpi');
        if ($this->getIsVbvRequired()) {
            $this->getHelper()->handleError(
                __(
                    'Only VBV / 3DS enrolled cards are accepted. Please try another card or a different payment method.'
                ),
                true
            );
        }

        if ($this->isPurchase()) {
            $this->capture($payment, $mdArray['amount']);
        } else {
            $this->authorize($payment, $mdArray['amount']);
        }

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
        $this->_payment = $payment;

        $mdArray = [];
        parse_str($md, $mdArray);

        $payment->setCcNumber($mdArray['pan']);

        $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Cavv\Purchase')
            ->setPayment($payment)
            ->setAmount($mdArray['amount'])
            ->setCavv($cavv);

        $result = $transaction->post();

        if ($result->getError()) {
            $this->getHelper()->critical(
                new LocalizedException(
                    __("Error on CAVV purchase: {$result->getResponseText()}\n" . print_r($result->getRawData()))
                )
            );

            $error = __('There was an error processing your card. Please use a different card or try a different payment method.');
            $this->getHelper()->handleError($error, true);
        }

        if (!$result->getSuccess()) {
            $this->getHelper()->handleError(__('The Transaction has been declined by your bank. Please use a different card or try a different payment method.'), true);
        }

        $this->_cavvSuccess($payment);

        // register the capture success
        $payment->setIsTransactionPending(false)
            ->registerCaptureNotification($mdArray['amount']);
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
            if ($voidPayment && $response->getXTransId()
                && strtoupper($response->getXType()) == self::REQUEST_TYPE_AUTH_ONLY
            ) {
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
     * Sets method code
     *
     * @param string $methodCode
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return void
     */
    public function setMethodCode($methodCode)
    {
    }
    
    /**
     * Sets path pattern
     *
     * @param string $pathPattern
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return void
     */
    public function setPathPattern($pathPattern)
    {
    }
}
