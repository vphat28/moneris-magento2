<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model;

use Moneris\CreditCard\Model\AbstractModel;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class VaultPayment extends AbstractModel
{
    const REQUEST_TYPE = 'res_mpitxn';

    /**
     * @var \Moneris\CreditCard\Model\Vault
     */
    private $vault;

    /**
     * VaultPayment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Moneris\CreditCard\Helper\Data $helper
     * @param Vault $modelVault
     * @param SessionManagerInterface $customerSession
     * @param Encryptor $encrypt
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Moneris\CreditCard\Model\Vault $vault
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
        Encryptor $encrypt,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Moneris\CreditCard\Model\Vault $vault,
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
            $encrypt,
            $countryFactory,
            $resource,
            $resourceCollection,
            $data
        );

        $this->vault = $vault;
    }

    /**
     * @return array
     */
    public function buildTransactionArray()
    {
        $payment = $this->getPayment();
        $amount = $this->getAmount();

        // must be exactly 20 alphanums
        $xid = sprintf("%'920d", rand());
        $merchantUrl = $this->getHelper()->getUrl(self::RETURN_URL_PATH, ['_secure' => true]);
        
        $dataKey = '';
        if ($payment->getAdditionalInformation('vault_id')) {
            $vaultId =$payment->getAdditionalInformation('vault_id');
            $vault = $this->vault->load($vaultId);
            $dataKey = $vault->getDataKey();
        }

        $txnArray = [
            'type'          => self::REQUEST_TYPE,
            'data_key'      => $dataKey,
            'xid'           => $xid,
            'amount'        => $amount,
            'MD'            => "amount=".$amount,
            'merchantUrl'   => $merchantUrl,
            'accept'        => getenv('HTTP_ACCEPT'),
            'userAgent'     => getenv('HTTP_USER_AGENT')
        ];
        
        return $txnArray;
    }

    /**
     * @return string
     */
    public function fetchCryptType()
    {
        $payment = $this->getPayment();
        $order = $payment->getOrder();

        // store the quote id in the session so the cart can be recovered if vbv goes awry
        $this->getHelper()->getCheckoutSession()->setMonerisccQuoteId($order->getQuoteId());
        $this->log(__METHOD__ . __LINE__ . ' 3D fetchCryptType');
        $mpiResponse = $this->post();
        $cryptType = $this->_interpretMpiResponse($mpiResponse, $payment);
        $this->getHelper()->getCheckoutSession()->setCryptType($cryptType);

        return $cryptType;
    }

    /**
     * @param $mpiResponse
     * @param $payment
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _interpretMpiResponse($mpiResponse, $payment)
    {
    
        $mpiMessage = $mpiResponse->getMpiMessage();
        $orderIncrementId = $payment->getOrder()->getIncrementId();
        $this->getHelper()->getCheckoutSession()->unsMonerisccMpiForm();
        switch ($mpiMessage) {
            case self::CRYPT_RESP_N:
                // card/issuer is not enrolled; proceed with transaction as usual?
                // Visa: merchant NOT liable for chargebacks
                // Mastercard: merchant IS liable for chargebacks
                $this->getHelper()->getCheckoutSession()->setMonerisccOrderId($orderIncrementId);
                $cryptType = self::CRYPT_SIX;
                break;
            case self::CRYPT_RESP_U:
                // card type does not participate
                // merchant IS liable for chargebacks
                $this->getHelper()->getCheckoutSession()->setMonerisccOrderId($orderIncrementId);
                $cryptType = self::CRYPT_SEVEN;
                break;
            case self::CRYPT_RESP_Y:
                // card is enrolled; the included form should be displayed for user authentication
                $form = $mpiResponse->getMpiInLineForm();
                $this->getHelper()->getCheckoutSession()->setMonerisccMpiForm($form);
                $this->getHelper()->getCheckoutSession()->setMonerisccOrderId($orderIncrementId);
    
                // crypt type will depend on the PaRes, but use 5 to signal enrollment
                $cryptType = self::CRYPT_FIVE;
    
                // abuse the additional_information field by making it hold the cryptType for capture ...
                $payment->setAdditionalInformation(['crypt' => $cryptType]);
                break;
            case self::CRYPT_RESP_NULL:
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Moneris endpoint is not responding.')
                );
            default:
                $cryptType = self::CRYPT_SEVEN;
                $this->getHelper()->getCheckoutSession()->setMonerisccOrderId($orderIncrementId);
                break;
        }
    
        return $cryptType;
    }
    
    public function post($txnArray = null)
    {
        if (!$txnArray) {
            $txnArray = $this->buildTransactionArray();
        }

        $payment = $this->getPayment();
        $methodCode = $payment->getMethodInstance()->getCode();
        
        $storeId = $this->getHelper()->getConfigData("payment/".$methodCode."/login", true);
        $apiToken =  $this->getHelper()->getConfigData("payment/".$methodCode."/password", true);

        $mpgTxn = new \mpgTransaction($txnArray);
        $mpgRequest = new \mpgRequest($mpgTxn);

        $mpgRequest->setProcCountryCode("CA");
        if ($this->getHelper()->isUsApi()) {
            $mpgRequest->setProcCountryCode("US"); //"US" for sending transaction to US environment
        }
        
        if ($this->getHelper()->isTest()) {
            $mpgRequest->setTestMode(true); //false or comment out this line for production transactions
        }
        
        $mpgHttpPost = new \mpgHttpsPost($storeId, $apiToken, $mpgRequest);
        $mpgResponse = $mpgHttpPost->getMpgResponse();
        return $mpgResponse;
    }
}
