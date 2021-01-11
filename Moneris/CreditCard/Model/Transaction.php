<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model;

use Moneris\CreditCard\Model\AbstractModel;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Moneris\CreditCard\Model\Method\Payment;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Moneris\CreditCard\SDK\mpgAvsInfo;
use Moneris\CreditCard\SDK\mpgCustInfo;
use Moneris\CreditCard\SDK\mpgCvdInfo;
use Moneris\CreditCard\SDK\mpgHttpsPost;
use Moneris\CreditCard\SDK\mpgHttpsPostStatus;
use Moneris\CreditCard\SDK\mpgRequest;
use Moneris\CreditCard\SDK\mpgTransaction;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Transaction extends AbstractModel
{
    const ERROR_MESSAGE = 'This should be overridden in the extending class.';
    const MCP_VERSION   = '1.0';

    /**
     * @var string
     */
    protected $_eventPrefix = 'moneriscc_transaction';

    /**
     * @var string
     */
    protected $_eventObject = 'transaction';

    /**
     * Set these in the child class
     */
    protected $_requestType     = 'none';

    /**
     * @var bool
     */
    protected $_isVoidable      = false;

    /**
     * @var bool
     */
    protected $_isRefundable    = false;

    /**
     * Only [cavv_]?purchases and [cavv_]?preauths can use AVS/CVD
     */
    protected $_canUseAvsCvd = false;

    /**
     * @var \Magento\Framework\DataObject
     */
    private $dataObject;

    /**
     * @var \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory
     */
    private $transactionSearchFactory;

    /**
     * Transaction constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Moneris\CreditCard\Helper\Data $helper
     * @param Vault $modelVault
     * @param SessionManagerInterface $customerSession
     * @param Encryptor $encryptor
     * @param DataObject $dataObject
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Moneris\CreditCard\Helper\Data $helper,
        Vault $modelVault,
        SessionManagerInterface $customerSession,
        Encryptor $encryptor,
        \Magento\Framework\DataObject $dataObject,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactionSearchFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $helper,
            $modelVault,
            $customerSession,
            $encryptor,
            $countryFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->transactionSearchFactory = $transactionSearchFactory;
        $this->dataObject = $dataObject;
    }

    protected function getIso4217Code($code)
    {
        $code = strtoupper($code);
        $isoCodes = array(
            'AFA' => array('Afghan Afghani', '971'),
            'AWG' => array('Aruban Florin', '533'),
            'AUD' => array('Australian Dollars', '036'),
            'ARS' => array('Argentine Pes', '032'),
            'AZN' => array('Azerbaijanian Manat', '944'),
            'BSD' => array('Bahamian Dollar', '044'),
            'BDT' => array('Bangladeshi Taka', '050'),
            'BBD' => array('Barbados Dollar', '052'),
            'BYR' => array('Belarussian Rouble', '974'),
            'BOB' => array('Bolivian Boliviano', '068'),
            'BRL' => array('Brazilian Real', '986'),
            'GBP' => array('British Pounds Sterling', '826'),
            'BGN' => array('Bulgarian Lev', '975'),
            'KHR' => array('Cambodia Riel', '116'),
            'CAD' => array('Canadian Dollars', '124'),
            'KYD' => array('Cayman Islands Dollar', '136'),
            'CLP' => array('Chilean Peso', '152'),
            'CNY' => array('Chinese Renminbi Yuan', '156'),
            'COP' => array('Colombian Peso', '170'),
            'CRC' => array('Costa Rican Colon', '188'),
            'HRK' => array('Croatia Kuna', '191'),
            'CPY' => array('Cypriot Pounds', '196'),
            'CZK' => array('Czech Koruna', '203'),
            'DKK' => array('Danish Krone', '208'),
            'DOP' => array('Dominican Republic Peso', '214'),
            'XCD' => array('East Caribbean Dollar', '951'),
            'EGP' => array('Egyptian Pound', '818'),
            'ERN' => array('Eritrean Nakfa', '232'),
            'EEK' => array('Estonia Kroon', '233'),
            'EUR' => array('Euro', '978'),
            'GEL' => array('Georgian Lari', '981'),
            'GHC' => array('Ghana Cedi', '288'),
            'GIP' => array('Gibraltar Pound', '292'),
            'GTQ' => array('Guatemala Quetzal', '320'),
            'HNL' => array('Honduras Lempira', '340'),
            'HKD' => array('Hong Kong Dollars', '344'),
            'HUF' => array('Hungary Forint', '348'),
            'ISK' => array('Icelandic Krona', '352'),
            'INR' => array('Indian Rupee', '356'),
            'IDR' => array('Indonesia Rupiah', '360'),
            'ILS' => array('Israel Shekel', '376'),
            'JMD' => array('Jamaican Dollar', '388'),
            'JPY' => array('Japanese yen', '392'),
            'KZT' => array('Kazakhstan Tenge', '368'),
            'KES' => array('Kenyan Shilling', '404'),
            'KWD' => array('Kuwaiti Dinar', '414'),
            'LVL' => array('Latvia Lat', '428'),
            'LBP' => array('Lebanese Pound', '422'),
            'LTL' => array('Lithuania Litas', '440'),
            'MOP' => array('Macau Pataca', '446'),
            'MKD' => array('Macedonian Denar', '807'),
            'MGA' => array('Malagascy Ariary', '969'),
            'MYR' => array('Malaysian Ringgit', '458'),
            'MTL' => array('Maltese Lira', '470'),
            'BAM' => array('Marka', '977'),
            'MUR' => array('Mauritius Rupee', '480'),
            'MXN' => array('Mexican Pesos', '484'),
            'MZM' => array('Mozambique Metical', '508'),
            'NPR' => array('Nepalese Rupee', '524'),
            'ANG' => array('Netherlands Antilles Guilder', '532'),
            'TWD' => array('New Taiwanese Dollars', '901'),
            'NZD' => array('New Zealand Dollars', '554'),
            'NIO' => array('Nicaragua Cordoba', '558'),
            'NGN' => array('Nigeria Naira', '566'),
            'KPW' => array('North Korean Won', '408'),
            'NOK' => array('Norwegian Krone', '578'),
            'OMR' => array('Omani Riyal', '512'),
            'PKR' => array('Pakistani Rupee', '586'),
            'PYG' => array('Paraguay Guarani', '600'),
            'PEN' => array('Peru New Sol', '604'),
            'PHP' => array('Philippine Pesos', '608'),
            'QAR' => array('Qatari Riyal', '634'),
            'RON' => array('Romanian New Leu', '946'),
            'RUB' => array('Russian Federation Ruble', '643'),
            'SAR' => array('Saudi Riyal', '682'),
            'CSD' => array('Serbian Dinar', '891'),
            'SCR' => array('Seychelles Rupee', '690'),
            'SGD' => array('Singapore Dollars', '702'),
            'SKK' => array('Slovak Koruna', '703'),
            'SIT' => array('Slovenia Tolar', '705'),
            'ZAR' => array('South African Rand', '710'),
            'KRW' => array('South Korean Won', '410'),
            'LKR' => array('Sri Lankan Rupee', '144'),
            'SRD' => array('Surinam Dollar', '968'),
            'SEK' => array('Swedish Krona', '752'),
            'CHF' => array('Swiss Francs', '756'),
            'TZS' => array('Tanzanian Shilling', '834'),
            'THB' => array('Thai Baht', '764'),
            'TTD' => array('Trinidad and Tobago Dollar', '780'),
            'TRY' => array('Turkish New Lira', '949'),
            'AED' => array('UAE Dirham', '784'),
            'USD' => array('US Dollars', '840'),
            'UGX' => array('Ugandian Shilling', '800'),
            'UAH' => array('Ukraine Hryvna', '980'),
            'UYU' => array('Uruguayan Peso', '858'),
            'UZS' => array('Uzbekistani Som', '860'),
            'VEB' => array('Venezuela Bolivar', '862'),
            'VND' => array('Vietnam Dong', '704'),
            'AMK' => array('Zambian Kwacha', '894'),
            'ZWD' => array('Zimbabwe Dollar', '716'),
        );

        if (isset($isoCodes[$code])) {
            return $isoCodes[$code][1];
        }

        return false;
    }

    protected function getOrderTransactions($order_id)
    {
        $transactions = $this->transactionSearchFactory->create()->addOrderIdFilter($order_id);
        return $transactions->getItems();
    }

    public function getUpdatedRequestType()
    {
        return ($this->getHelper()->isUsApi() &&
        !strstr($this->_requestType, self::US_PREFIX) ?
            self::US_PREFIX : '') .
        $this->_requestType;
    }

    /**
     * @return string, the request type
     */
    public function getRequestType()
    {
        return $this->_requestType;
    }

    public function getIsVoidable()
    {
        return $this->_isVoidable;
    }

    public function getIsRefundable()
    {
        return $this->_isRefundable;
    }

    public function getPaymentAction()
    {
        return $this->getHelper()->getPaymentAction();
    }

    /**
     * Returns a unique customer ID that is human  readable
     * Max length of 50
     * @return string
     */
    public function getCustomerId()
    {
        if (!$this->getHelper()->getModuleConfig('use_customer_name')) {
            return $this->getPayment()->getOrder()->getCustomerId();
        }

        $payment = $this->getPayment();

        $billingObj = $payment->getOrder()->getBillingAddress();
        $customerId = self::CUSTOMER_ID_DELIM . $payment->getOrder()->getCustomerId();

        $fullCustomerName = $billingObj->getFirstname() . self::CUSTOMER_ID_DELIM . $billingObj->getLastname();
        // we can only send 50 chars
        $customerIdLength = strlen($customerId);
        $fullCustomerName = substr(
            $fullCustomerName,
            0,
            (self::MAX_CHARS_CUSTOMER_ID - $customerIdLength)
        ) . $customerId;
        return $fullCustomerName;
    }

    /**
     * Returns a unique order ID consisting of the order increment ID and
     * a generated tail.
     * Max length of 50
     *
     * @return string
     */
    public function generateUniqueOrderId($length = 20)
    {
        $order = $this->getPayment()->getOrder();

        if (!$order) {
            return '';
        }

        $incrementId = $order->getIncrementId();

        $tail = '';
        for ($i = 0; $i < $length; $i++) {
            $tail .= rand(0, 9);
        }

        return "{$incrementId}-{$tail}";
    }

    /**
     * Builds the appropriate transaction array for an MpgTransaction
     * to be built for this transaction.
     *
     * Override in child class.
     *
     * @return array
     */
    public function buildTransactionArray()
    {
        return [self::ERROR_MESSAGE];
    }

    /**
     * Builds a Moneris_MpgTransaction from the $txnArray.
     *
     * @param array $txnArray
     * @return Moneris_MpgTransaction
     */
    public function buildMpgTransaction($txnArray)
    {
        return new mpgTransaction($txnArray);
    }

    /**
     * Builds a Moneris_MpgRequest from the $mpgTxn
     *
     * @param Moneris_MpgTransaction $mpgTxn
     * @return Moneris_MpgRequest
     */
    public function buildMpgRequest($mpgTxn)
    {
        
        $mpgRequest = new mpgRequest($mpgTxn);
        
        $mpgRequest->setProcCountryCode("CA");
        if ($this->getHelper()->isUsApi()) {
            $mpgRequest->setProcCountryCode("US"); //"US" for sending transaction to US environment
        }

        if ($this->getHelper()->isTest()) {
            $mpgRequest->setTestMode(true); //false or comment out this line for production transactions
        }

        return $mpgRequest;
    }

    /**
     * Builds a Moneris_MpgHttpsPost from the $mpgRequest
     *
     * @param Moneris_MpgTransaction $mpgRequest
     * @return Moneris_MpgHttpsPost
     */
    // we cannot type cast it as there are two types Moneris_MpgRequest and Monerisus_MpgRequest
    public function buildMpgHttpsPost($mpgRequest)
    {
        $payment = $this->getPayment();
        $methodCode = $payment->getMethodInstance()->getCode();

        $storeId = $this->getHelper()->getScopeConfig()->getValue("payment/moneris/".$methodCode."/login");

        $encryptor = $this->getHelper()->getDecryptor();

        $storeId = $encryptor->decrypt($storeId);

        $apiToken =  $this->getHelper()->getScopeConfig()->getValue("payment/moneris/".$methodCode."/password");
        $apiToken = $encryptor->decrypt($apiToken);

        $this->getHelper()->log(__FILE__." ".__LINE__." store $storeId");
        $this->getHelper()->log(__FILE__." ".__LINE__." api $apiToken");

        $mpgHttpPost = new mpgHttpsPost($storeId, $apiToken, $mpgRequest);
        return $mpgHttpPost;
    }

    /**
     * Check Order status
     *
     * @param $mpgRequest
     * @param string $methodCode
     * @return \mpgHttpsPost
     * @throws LocalizedException
     */
    public function buildMpgHttpsPostStatus($mpgRequest, $methodCode)
    {
        $storeId = $this->getHelper()->getConfigData("payment/".$methodCode."/login", true);

        if (empty($storeId)) {
            $storeId = $this->getHelper()->getConfigData("payment/moneris/".$methodCode."/login", true);
        }

        $apiToken =  $this->getHelper()->getConfigData("payment/".$methodCode."/password", true);

        if (empty($apiToken)) {
            $apiToken =  $this->getHelper()->getConfigData("payment/moneris/".$methodCode."/password", true);
        }

        $this->getHelper()->log(__FILE__." ".__LINE__." store $storeId");
        $this->getHelper()->log(__FILE__." ".__LINE__." api $apiToken");

        $mpgHttpPost = new mpgHttpsPostStatus($storeId, $apiToken, 'true', $mpgRequest);
        return $mpgHttpPost;
    }

    /**
     * Gets the Moneris_MpgResponse from the $mpgHttpsPost.
     *
     * @param Moneris_MpgHttpsPost $mpgHttpsPost
     * @return Moneris_MpgResponse
     */
    // TODO we have two classes like this . cant define it Moneris_MpgHttpsPost Monerisus_MpgHttpsPost
    public function getMpgResponse($mpgHttpsPost)
    {
        return $mpgHttpsPost->getMpgResponse();
    }

    /**
     * Returns true if $code is a successful one; else, false.
     *
     * @param mixed $code
     * @return bool
     */
    public function getIsSuccessfulResponseCode($code)
    {
        return (is_numeric($code) && $code >= 0 && $code < 50);
    }

    /**
     * Posts the transaction to Moneris. Uses buildTransactionArray() if none is passed in.
     *
     * @param array $txnArray=null
     * @return \Magento\Framework\DataObject $result
     */
    public function post($txnArray = null, $save = true)
    {
        try {
            if (!$txnArray) {
                $txnArray = $this->buildTransactionArray();
            }
            file_put_contents(BP . '/var/log/ch_moneris.log', PHP_EOL . print_r($txnArray, 1), FILE_APPEND);
            $result = $this->_post($txnArray, $save);
            $this->setResult($result);
        } catch (\Exception $e) {
            $this->getHelper()->critical($e);
        }
        
        return $result;
    }

    /**
     * Posts the transaction.
     *
     * @param array $txnArray
     * @return \Magento\Framework\DataObject result
     */
    protected function _post($txnArray)
    {
        $mpgTxn = $this->buildMpgTransaction($txnArray);
        $mpgRequest = $this->buildMpgRequest($mpgTxn);
        $mpgHttpsPost = $this->buildMpgHttpsPost($mpgRequest);
        $mpgResponse = $this->getMpgResponse($mpgHttpsPost);
        $result = $this->_getResultFromMpgResponse($mpgResponse);
        $responseCode = $result->getResponseCode();
        $responses = $this->getHelper()->getResponses();
        if ($result->getError()) {
            $result->setStatus(Payment::STATUS_ERROR);
        } elseif (!$result->getSuccess()) {
            if (isset($responses[$responseCode])) {
                $message = $responses[$responseCode]['message'];
            } else {
                $message = $result->getMessage();
            }
            
            $result->setStatus(Payment::STATUS_DECLINED)
                ->setDescription($message)
                ->setResponseText($message);
        }

        // check avs/cvd
        if ($result->getSuccess() && $this->_canUseAvsCvd) {
            if (!$this->_checkAvs($result)) {
                return $result;
            }

            if (!$this->_checkCvd($result)) {
                return $result;
            }
        }

        $this->_updatePayment($result);
        $receipt = $this->_buildReceipt($result);

        $this->getCustomerSession()->setMoneriscccData($receipt);

        return $result;
    }

    /**
     * @param $txnArray
     * @param $methodCode
     * @return DataObject
     * @throws LocalizedException
     */
    public function checkOrderStatus($txnArray, $methodCode)
    {
        $mpgTxn = $this->buildMpgTransaction($txnArray);
        $mpgRequest = $this->buildMpgRequest($mpgTxn);
        $mpgHttpsPost = $this->buildMpgHttpsPostStatus($mpgRequest, $methodCode);
        $mpgResponse = $this->getMpgResponse($mpgHttpsPost);
        $result = $this->_getResultFromMpgResponse($mpgResponse);

        return $result;
    }

    /**
     * Updates the payment object with the $result data.
     *
     * @param DataObject $result
     * @return $this
     * @throws LocalizedException
     */
    protected function _updatePayment(\Magento\Framework\DataObject $result)
    {
        $payment = $this->getPayment();

        if (!$payment) {
            return $this;
        }

        $rawData = $result->getData('raw_data');

        if ($payment->getAdditionalInformation('vault_id_issuer')) {
            if (isset($rawData['IssuerId']) && $rawData['IssuerId']) {
                /** @var Vault $vault */
                $vault = ObjectManager::getInstance()->create(Vault::class);
                $vault->load($payment->getAdditionalInformation('vault_id_issuer'));
                $vault->setData('issuer_id', $rawData['IssuerId']);
                $vault->save();
            }
        }

        $status = $result->getSuccess() == 0 ||
            $result->getSuccess() < 50 ||
            $result->getSuccess() == '000' ||
            $result->getSuccess() == '00'; // TODO recurring needs 001
        $this->getPayment()
            ->setStatus($status)
            ->setCcApproval($result->getAuthCode())
            ->setCcAvsStatus($result->getAvsResultCode())
            ->setCcCidStatus($result->getCvdResultCode());

        // dont change ID on refund.. so we can refund again.
        
        if ($this->_requestType != 'refund') {
            $this->getPayment()
            ->setLastTransId($result->getLastTransId())
            ->setCcTransId($result->getTxnNumber());
            
            if (!$this->getPayment()->getParentTransactionId()
                || $result->getTxnNumber() != $this->getPayment()->getParentTransactionId()
            ) {
                $this->getPayment()->setTransactionId($result->getTxnNumber());
            }
            
            $this->getPayment()->setIsTransactionClosed(0)
            ->setTransactionAdditionalInfo(
                self::REAL_TRANSACTION_ID_KEY,
                $result->getTxnNumber()
            );

            $this->getPayment()->setIsTransactionClosed(0)
            ->setTransactionAdditionalInfo(
                'orig_order_id',
                $result->getLastTransId()
            );

        }

        if (!$this->getPayment()->getLastTransId()) {
            $this->getPayment()
                ->setLastTransId($result->getLastTransId());
        }
        if (!$this->getPayment()->getTransactionId()) {
            $this->getPayment()
                ->setTransactionId($result->getLastTransId());
        }

        if (!$this->getPayment()->getCcTransId()) {
            $this->getPayment()
                ->setCcTransId($result->getTxnNumber());
        }

        return $this;
    }

    /**
     * Builds an array holding the data for the transaction receipt.
     *
     * @param \Magento\Framework\DataObject $result
     * @return array $receipt
     */
    protected function _buildReceipt(\Magento\Framework\DataObject $result)
    {
        // TODO: We may need to detect the store currency here
        $currency = "";
        if ($this->getPayment()->getOrder()) {
            $currency = $this->getPayment()->getOrder()->getOrderCurrency()->getCurrencyCode();
        }
        
        $receipt = [
            'trnId'             => $result->getTxnNumber(),
            'trnOrderNumber'    => $result->getReferenceNum(),
            'trnAmount'         => $this->getAmount(),
            'currency'          => $currency,
            'authCode'          => $result->getAuthCode(),
            'messageText'       => $result->getMessage(),
            'trnDate'           => $result->getTransDate() . ' ' . $result->getTransTime()
        ];

        return $receipt;
    }

    /**
     * Puts the data from an mpgResponse in to a more useful \Magento\Framework\DataObject.
     *
     * @param Moneris_MpgResponse $mpgResponse
     * @return \Magento\Framework\DataObject $result
     */
    protected function _getResultFromMpgResponse($mpgResponse)
    {
        $this->dataObject->setData(
            [
                'success'           => $this->getIsSuccessfulResponseCode($mpgResponse->getResponseCode()),
                'error'             => !is_numeric($mpgResponse->getResponseCode()),
                'response_code'     => $mpgResponse->getResponseCode(),
                'last_trans_id'     => $mpgResponse->getReceiptId(),
                'cvd_result_code'   => $mpgResponse->getCvdResultCode(),
                'avs_result_code'   => ($mpgResponse->getAvsResultCode() != 'null') ?
                    $mpgResponse->getAvsResultCode() :
                    null,
                'message'           => $mpgResponse->getMessage(),
                'description'       => $mpgResponse->getMessage(),
                'txn_number'        => $mpgResponse->getTxnNumber(),
                'reference_num'     => $mpgResponse->getReferenceNum(),
                'auth_code'         => $mpgResponse->getAuthCode(),
                'iso_code'          => $mpgResponse->getISO(),
                'trans_date'        => $mpgResponse->getTransDate(),
                'trans_time'        => $mpgResponse->getTransTime(),
                'raw_data'          => $mpgResponse->getMpgResponseData()
            ]
        );

        return $this->dataObject;
    }

    /**
     * Sends a void transaction if auth mode.
     * Sends a refund if $result if auth & capture mode.
     *
     * @param \Magento\Framework\DataObject $result
     * @return $this
     */
    protected function _undoTransaction($result)
    {
        if ($this->getIsVoidable()) {
            $this->_voidTransaction($result);
        } elseif ($this->getIsRefundable()) {
            $this->_refundTransaction($result);
        }

        return $this;
    }

    protected function _voidTransaction($result)
    {
        $this->getHelper()->log('voiding');

        $voidPayment = $this->getHelper()->getObject('Magento\Sales\Model\Order\Payment')
            ->setLastTransId($result->getLastTransId())
            ->setCcTransId($result->getTxnNumber());
        $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\VoidTransaction')
            ->setPayment($voidPayment)
            ->setCryptType(self::CRYPT_SEVEN);

        try {
            $transaction->post();
        } catch (\Exception $e) {
            $this->getHelper()->critical($e);
        }

        return $this;
    }

    protected function _refundTransaction($result)
    {
        $this->getHelper()->log('refunding');

        $refundPayment = $this->getHelper()->getObject('Magento\Sales\Model\Order\Payment')
            ->setLastTransId($result->getLastTransId())
            ->setCcTransId($result->getTxnNumber());
        $transaction = $this->getHelper()->getObject('Moneris\CreditCard\Model\Transaction\Refund')
            ->setPayment($refundPayment)
            ->setAmount($this->getAmount())
            ->setCryptType(self::CRYPT_SEVEN);

        try {
            $transaction->post();
        } catch (\Exception $e) {
            $this->getHelper()->critical($e);
        }

        return $this;
    }

    /**
     * Returns true if AVS is successful or disabled.
     * Else, returns false (AVS is enabled and has failed).
     *
     * @param \Magento\Framework\DataObject $result
     * @return bool
     */
    protected function _checkAvs(\Magento\Framework\DataObject $result)
    {
        if (!$this->getHelper()->getModuleConfig('avszip')) {
            return true;
        }

        $this->getHelper()->log('AVS result code: ' . $result->getAvsResultCode());
        $avsResultCode = $result->getAvsResultCode();

        if (!$avsResultCode || strcmp($avsResultCode, 'null') === 0) {
            return true;
        }

        $avsSuccessCodes = $this->getHelper()->getAvsSuccessCodes();
        if (in_array($avsResultCode, $avsSuccessCodes)) {
            return true;
        }

        // non-success code
        $this->getHelper()->log($this->getHelper()->getModuleConfig('payment_action') . ' Failed AVS');

        $status = __('FAILED');
        $message = $this->getHelper()->getResponseTextOverride('AVS', $status);
        if (!$message) {
            $message = __('Transaction Failed AVS Match');
        }

        $this->_undoTransaction($result);

        $result->setStatus($status)
            ->setResponseText($message)
            ->setDescription($message)
            ->setLastTransId(false)
            ->setError(true);

        $this->_updatePayment($result);

        return false;
    }

    /**
     * Returns true if CVD check is successful or disabled.
     * Else, returns false (CVD check is enabled and has failed).
     *
     * @param \Magento\Framework\DataObject $result
     * @return bool
     */
    protected function _checkCvd(\Magento\Framework\DataObject $result)
    {
        // IF we are requiring liability shift we do not need CVV/AVS validation
        if (!$this->getHelper()->getConfigData('payment/moneris/chmoneriscc/useccv') || $this->getHelper()->getConfigData('require_vbv')) {
            return true;
        }

        $cvdResultCode = $result->getCvdResultCode();
        // KL: Regardless, save CVD data
        $this->getHelper()->getCheckoutSession()->setMonerisCavvCvdResult($cvdResultCode);

        if (!$cvdResultCode || strcmp($cvdResultCode, 'null') === 0) {
            return true;
        }

        //Canadian API usually return 2 digit cvd code US API appears to be returning 1
        $cvdResultCode = trim(strlen(trim($cvdResultCode)) > 1 ? $cvdResultCode[1] : $cvdResultCode);
        $this->getHelper()->log(__METHOD__ . __LINE__ . ' cvd result code: ' . $cvdResultCode);

        $cvdSuccessCodes = $this->getHelper()->getCvdSuccessCodes();

        if (in_array($cvdResultCode, $cvdSuccessCodes)) {
            return true;
        }

        //non-success code
        $this->getHelper()->log(__METHOD__ . __LINE__ . $this->getPaymentAction() . " Failed CVD ");

        $status = 'FAILED';
        $message = $this->getHelper()->getResponseTextOverride('CVD', $status);
        if (!$message) {
            $message = __(
                'Card Verification Number mismatch. $1 : $2',
                $this->cvdResponseCodes[$cvdResultCode[0]],
                $this->cvdResponseCodes[$cvdResultCode[1]]
            );
        }

        $this->_undoTransaction($result);

        $result->setStatus($status)
            ->setResponseText($message)
            ->setDescription($message)
            ->setLastTransId(false)
            ->setError(true);

        $this->_updatePayment($result);

        return false;
    }

    /**
     * @param $order
     * @return \mpgCustInfo
     * @throws \Exception
     */
    public function buildMpgCustInfo($order)
    {
        $billingObj = $order->getBillingAddress();

        if (!$billingObj) {
            throw new LocalizedException(__('Invalid billing data.'));
        }

        $billing = $this->objToArray($billingObj);

        $shippingObj = $order->getShippingAddress();

        $shipping = [];
        if ($shippingObj) {
            $shipping = $this->objToArray($shippingObj);
        }

        $mpgCustInfo = new mpgCustInfo();

        $shippingCost = $order->getShippingAmount();
        if ($shippingCost) {
            $shippingCost = $this->getHelper()->formatPrice($shippingCost);
            $shipping['shipping_cost'] = $shippingCost;
            $billing['shipping_cost'] = $shippingCost;
        }
        
        $taxCost = $order->getTaxAmount();
        if ($taxCost) {
            $taxCost = $this->getHelper()->formatPrice($taxCost);
            $shipping['tax1'] = $taxCost;
            $billing['tax1'] = $taxCost;
        }
        
        $mpgCustInfo->setShipping($shipping);
        $mpgCustInfo->setBilling($billing);
        $mpgCustInfo->setEmail($order->getCustomerEmail());

        $items = $order->getAllItems();
        if ($items) {
            foreach ($items as $key => $item) {
                $monerisItem[$key] = [
                    'name'=>$item->getName(),
                    'quantity'=>$item->getQtyOrdered(),
                    'product_code'=>$item->getSku(),
                    'extended_amount'=>$this->getHelper()->formatPrice(
                        $item->getQtyOrdered() * $item->getOriginalPrice()
                    )
                ];
                $mpgCustInfo->setItems($monerisItem[$key]);
            }
        }

        return $mpgCustInfo;
    }

    /**
     * Builds an MpgAvsInfo for the $address
     *
     * @param \Magento\Framework\DataObject $address
     * @return Moneris_MpgAvsInfo
     */
    public function buildMpgAvsInfo(\Magento\Framework\DataObject $address)
    {
        if (!$this->getHelper()->getModuleConfig('avszip')) {
            return null;
        }

        $avs = [
            'avs_street_number' =>'',
            'avs_street_name' =>'',
            'avs_zipcode' => $address->getPostcode()
        ];

        $mpgAvsInfo = new mpgAvsInfo($avs);

        return $mpgAvsInfo;
    }

    /**
     * Builds an MpgCvdInfo for the $payment
     *
     * @param \Magento\Framework\DataObject $payment
     * @return Moneris_MpgCvdInfo
     */
    public function buildMpgCvdInfo(\Magento\Framework\DataObject $payment)
    {
        if (!$this->getHelper()->getConfigData('payment/moneris/chmoneriscc/useccv')) {
            return null;
        }
        $cvv = [
            'cvd_indicator' => '1',
            'cvd_value'     => $payment->getCcCid()
        ];
        $this->helper->log('CVD sent');
        
        $mpgCvdInfo = new mpgCvdInfo($cvv);
        
        return $mpgCvdInfo;
    }
}
