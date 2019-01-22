<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model;

use Moneris\CreditCard\Model\Transaction;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Session\SessionManagerInterface;

class VaultSaveService extends Transaction
{
    private $requestAddType = 'res_add_cc';
    private $requestEditType = 'res_update_cc';

    /**
     * VaultSaveService constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Moneris\CreditCard\Helper\Data $helper
     * @param Vault $modelVault
     * @param SessionManagerInterface $customerSession
     * @param Encryptor $encryptor
     * @param DataObject $dataObject
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactionSearchFactory
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
        DataObject $dataObject,
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
            $dataObject,
            $countryFactory,
            $transactionSearchFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function process($data)
    {
        if (!$data) {
            return $this;
        }
        
        $this->post($data);
    }

    public function deleteVault($id)
    {
        if ($id) {
            $this->modelVault->load($id);
        }
        
        $type = 'res_delete';
        $txnArray = [
            'type' => $type,
            'data_key' => $this->modelVault->getDataKey()
        ];
        $mpgTxn = $this->buildMpgTransaction($txnArray);
        $mpgRequest = $this->buildMpgRequest($mpgTxn);
        $mpgHttpPost = $this->buildMpgHttpsPost($mpgRequest);
        $mpgResponse = $mpgHttpPost->getMpgResponse();
        if (isset($mpgResponse) && $mpgResponse) {
            $this->modelVault->delete();
        }
    }

    public function _buildTransArray($data)
    {
        $cryptType = '1';
        $customer = $this->getCustomerData();
        /*********************** _prepareData to request Moneris        */
        $type = $this->requestAddType;
        if (isset($data['payment']['data_key']) && $data['payment']['data_key']) {
            $type = $this->requestEditType;
            $cryptType = '7';
        }
        
        /*********************** Transactional Associative Array **********************/
        $transArray = [
            'type' => $type,
            'cust_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'pan' => $data['payment']['cc_number'],
            'expdate' => $this->getFormattedExpiry($data['payment']),
            'crypt_type' => $cryptType
        ];
        if (isset($data['payment']['data_key']) && $data['payment']['data_key']) {
            $transArray['data_key'] = $data['payment']['data_key'];
        }
        
        return $transArray;
    }

    public function getCustomerData()
    {
        if ($this->customerSession->isLoggedIn()) {
            return  $this->customerSession->getCustomer();
        }
        
        return null;
    }

    /**
     * Posts the transaction to Moneris. Uses buildTransactionArray() if none is passed in.
     *
     * @param array $txnArray=null
     * @param array $save=true
     * @return \Magento\Framework\DataObject $result
     */
    public function post($txnArray = null, $save = true)
    {
        try {
            if (!$txnArray) {
                $txnArray = $this->buildTransactionArray();
            }
            
            $result = $this->_post($txnArray);
            $this->setResult($result);
        } catch (\Exception $e) {
            $this->getHelper()->critical($e);
        }
        
        return $result;
    }

    private function _prepareData($data, $post)
    {
        if (isset($post['vailt_id']) && $post['vailt_id']) {
            $this->modelVault->load($post['vailt_id']);
        }

        $this->modelVault->setCcExpMonth($post['payment']['cc_exp_month']);
        $this->modelVault->setCcExpYear($post['payment']['cc_exp_year']);
        $this->modelVault->setCreatedDate(gmdate("Y-m-d\TH:i:s\Z"));

        if ($data->getPaymentType()) {
            $this->modelVault->setPaymentType($data->getPaymentType());
            $this->modelVault->setCardType($post['payment']['cc_type']);
        }
        
        if ($data->getDataKey()) {
            $this->modelVault->setDataKey($data->getDataKey());
        }
        
        if ($data->getResDataCustId()) {
            $this->modelVault->setCustomerId($data->getResDataCustId());
        }
        
        if ($data->getResDataMaskedPan()) {
            $this->modelVault->setCcLast($data->getResDataMaskedPan());
        }
        
        if ($data->getResDataEmail()) {
            $this->modelVault->setCustomerEmail($data->getResDataEmail());
        }
        
        if ($data->getResDataExpDate()) {
            $this->modelVault->setCardExpire($data->getResDataExpDate());
        }

        $this->modelVault->save();
    }

    protected function _post($txnArray)
    {
        $dataPost = $txnArray;
        $txnArray = $this->_buildTransArray($txnArray);
        $mpgTxn = $this->buildMpgTransaction($txnArray);
        $mpgRequest = $this->buildMpgRequest($mpgTxn);
        $mpgHttpPost = $this->buildMpgHttpsPost($mpgRequest);
        $mpgResponse=$mpgHttpPost->getMpgResponse();
        
        if (isset($mpgResponse) && $mpgResponse && $mpgResponse->getResponseCode() == '001') {
            $this->_prepareData($mpgResponse, $dataPost);
        }
    }

    public function buildMpgTransaction($txnArray)
    {
        $mpgTxn = parent::buildMpgTransaction($txnArray);
        return $mpgTxn;
    }
    public function getFormattedExpiry($data)
    {
        return $this->getHelper()->getFormattedExpiry($data['cc_exp_month'], $data['cc_exp_year']);
    }
    
    public function addVault($data)
    {
        $this->modelVault->setCreatedDate(gmdate("Y-m-d\TH:i:s\Z"));
        if ($data['payment_type']) {
            $this->modelVault->setPaymentType($data['payment_type']);
        }
        
        if ($data['data_key']) {
            $this->modelVault->setDataKey($data['data_key']);
        }
        
        if ($data['cust_id']) {
            $this->modelVault->setCustomerId($data['cust_id']);
        }
        
        if ($data['f4l4']) {
            $this->modelVault->setCcLast($data['f4l4']);
            $this->modelVault->setCardType($this->getCreditCardType($data['f4l4']));
        }
        
        if ($data['expiry_date']) {
            $this->modelVault->setCardExpire($data['expiry_date']);
            $expDate = str_split($data['expiry_date'], 2);
            $this->modelVault->setCcExpMonth($expDate[1]);
            $this->modelVault->setCcExpYear('20'.$expDate[0]);
        }

        $this->modelVault->save();
    }
    
    private function getCreditCardType($cLast)
    {
        $cLast = preg_replace('/[^0-9]/', '', $cLast);
        $inn = (int) mb_substr($cLast, 0, 2);
        if ($inn >= 40 && $inn <= 49) {
            $type = 'VI';
        } elseif ($inn >= 51 && $inn <= 55) {
            $type = 'MC';
        } elseif ($inn >= 60 && $inn <= 65) {
            $type = 'DI';
        } elseif ($inn == 37) {
            $type = 'AE';
        } elseif ($inn == 30 || $inn == 38) {
            $type = 'DN';
        } elseif ($inn == 35) {
            $type = 'JCB';
        } else {
            $type = 'OT';
        }
        
        return $type;
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
        $methodCode = 'chmoneris';
        $storeId = $this->getHelper()->getConfigData("payment/".$methodCode."/login", true);
        $apiToken =  $this->getHelper()->getConfigData("payment/".$methodCode."/password", true);
    
        $this->getHelper()->log(__FILE__." ".__LINE__." store $storeId");
        $mpgHttpPost = new \mpgHttpsPost($storeId, $apiToken, $mpgRequest);
        return $mpgHttpPost;
    }
}
