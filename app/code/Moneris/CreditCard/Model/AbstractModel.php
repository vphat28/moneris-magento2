<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Moneris OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractModel extends \Magento\Framework\Model\AbstractModel
{
    const RETURN_URL_PATH = 'moneriscc/index/returns';
    // blank state field
    const EMPTY_STATE = '--';
    // MPI
    const ACS_TYPE = 'acs';
    const TXN_TYPE = 'txn';
    const CRYPT_RESP_NULL = 'null';
    const CRYPT_RESP_N = 'N';
    const CRYPT_RESP_Y = 'Y';
    const RESPONSE_TRUE = 'true';
    const CRYPT_RESP_U = 'U';
    const CRYPT_FIVE = '5';
    const CRYPT_SIX = '6';
    const CRYPT_SEVEN = '7';

    // TRANSACTION
    const CAVV_FIELD = 'cavv';
    const CRYPT_FIELD = 'crypt_type';
    const VAULT_CAVV_PURCHASE = 'res_cavv_purchase_cc';
    const VAULT_CAVV_PREAUTH = 'res_cavv_preauth_cc';

    //  trasn types
    const CAVV_PURCHASE = 'cavv_purchase';
    const CAVV_PREAUTH = 'cavv_preauth';
    const PREAUTH = 'preauth';
    const COMPLETION = 'completion';
    const REFUND = 'refund';
    const PURCHASE_CORRECTION = 'purchasecorrection';
    const PURCHASE = 'purchase';
    const US_PREFIX = "us_";
    const COMPLETION_US = 'us_completion';
    const REAL_TRANSACTION_ID_KEY  = 'real_transaction_id';
    const MAX_CHARS_CUSTOMER_ID = 50;
    const CUSTOMER_ID_DELIM = '-';

    /**
     * @var
     */
    public $payment;

    /**
     * @var
     */
    public $amount;

    /**
     * @var \Moneris\CreditCard\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;
    
    /**
     * @var \Moneris\CreditCard\Model\Vault
     */
    public $modelVault;
    
    /**
     * @var \Magento\Framework\Encryption\Encryptor
     */
    public $encryptor;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    public $countryFactory;

    /**
     * @var array
     */
    public $cvdResponseCodes = [
        '0' => 'CVD value is deliberately bypassed or is not provided by the merchant.',
        '1' => 'CVD value is present.',
        '2' => 'CVD value is on the card, but is illegible.',
        '9' => 'Cardholder states that the card has no CVD imprint.',

        'M' => 'Match',
        'N' => 'No Match',
        'P' => 'Not Processed',
        'S' => 'CVD should be on the card, but Merchant has indicated that CVD is not present',
        'U' => 'Issuer is not a CVD participant'
    ];

    /**
     * AbstractModel constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Moneris\CreditCard\Helper\Data $helper
     * @param Vault $modelVault
     * @param SessionManagerInterface $customerSession
     * @param Encryptor $encryptor
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Moneris\CreditCard\Helper\Data $helper,
        \Moneris\CreditCard\Model\Vault $modelVault,
        SessionManagerInterface $customerSession,
        Encryptor $encryptor,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        $this->helper = $helper;
        $this->modelVault = $modelVault;
        $this->customerSession = $customerSession;
        $this->encryptor = $encryptor;
        $this->countryFactory = $countryFactory;
        if (!class_exists('Moneris_MpgTransaction')) {
            $files = glob(BP .  '/lib/Moneris*/*.php');
            foreach ($files as $f) {
                require_once $f;
            }
        }

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;
        return $this;
    }

    public function setAmount($x)
    {
        $this->amount = $x;
        return $this;
    }

    public function getAmount()
    {
        return number_format($this->amount, 2, '.', '');
    }

    /**
     * @return Payment|mixed
     */
    public function getPayment()
    {
        return $this->payment;
    }

    public function log($x)
    {
        $this->helper->log($x);
    }

    public function getHelper()
    {
        return $this->helper;
    }

    public function getFormattedExpiry($payment)
    {
        return $this->getHelper()->getFormattedExpiry($payment->getCcExpMonth(), $payment->getCcExpYear());
    }

    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * Creates a Moneris-friendly array out of an address object.
     *
     * @param \Magento\Framework\DataObject $addressObj
     * @return array
     */
    public function objToArray(DataObject $addressObj)
    {
        $stateCode = $addressObj->getRegionCode();
        if (!$stateCode) {
            $stateCode = self::EMPTY_STATE;
        }
        
        $countryName = $this->_getCountryName($addressObj->getCountryId());
        
        $address = [
            'first_name' => $addressObj->getFirstname(),
            'last_name' => $addressObj->getLastname(),
            'company_name' => $addressObj->getCompany(),
            'address' => $addressObj->getData('street'),
            'city' =>$addressObj->getCity(),
            'province' => $stateCode,
            'postal_code' => $addressObj->getPostcode() ,
            'country' => $countryName,
            'phone_number' =>  $addressObj->getTelephone(),
            'fax' => $addressObj->getFax(),
            'tax1' => 0,
            'tax2' => 0,
            'tax3' => 0,
            'shipping_cost' => 0
        ];

        return $address;
    }

    /**
     * @param $countryId
     * @return string
     */
    public function _getCountryName($countryId)
    {
        $country = $this->countryFactory->create()->loadByCode($countryId);
        $countryName = $country->getName();
        if ($country) {
            $countryName = $country->getName();
        }
        
        return $countryName;
    }
}
