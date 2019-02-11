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

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Transaction extends AbstractModel
{
    const ERROR_MESSAGE = 'This should be overridden in the extending class.';

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
        return new \mpgTransaction($txnArray);
    }

    /**
     * Builds a Moneris_MpgRequest from the $mpgTxn
     *
     * @param Moneris_MpgTransaction $mpgTxn
     * @return Moneris_MpgRequest
     */
    public function buildMpgRequest($mpgTxn)
    {
        
        $mpgRequest = new \mpgRequest($mpgTxn);
        
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

        $mpgHttpPost = new \mpgHttpsPost($storeId, $apiToken, $mpgRequest);
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

        $mpgHttpPost = new \mpgHttpsPostStatus($storeId, $apiToken, 'true', $mpgRequest);
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
            if (@$rawData['IssuerId']) {
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
        if (!$this->getHelper()->getModuleConfig('useccv') || $this->getHelper()->getModuleConfig('require_vbv')) {
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

        $mpgCustInfo = new \mpgCustInfo();

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

        $mpgAvsInfo = new \mpgAvsInfo($avs);

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
        if (!$this->getHelper()->getModuleConfig('useccv')) {
            return null;
        }

        $cvv = [
            'cvd_indicator' => '1',
            'cvd_value'     => $payment->getCcCid()
        ];
        
        $mpgCvdInfo = new \mpgCvdInfo($cvv);
        
        return $mpgCvdInfo;
    }
}
