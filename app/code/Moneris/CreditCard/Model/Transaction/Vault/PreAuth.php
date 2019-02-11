<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Transaction\Vault;

use Moneris\CreditCard\Model\Method\Payment;
use Moneris\CreditCard\Model\Transaction\PreAuth as TransactionPreAuth;

/**
 * Moneres OnSite Payment Method model.
 **/
class PreAuth extends TransactionPreAuth
{
    protected $_requestType = self::VAULT_CAVV_PREAUTH;

    public function buildTransactionArray()
    {
        $payment = $this->getPayment();

        if (!$payment) {
            return [];
        }
        
        $dataKey = '';
        
        if ($this->getHelper()->getCheckoutSession()->getMonerisccVaultId()) {
            $vaultId = $this->getHelper()->getCheckoutSession()->getMonerisccVaultId();
            $vault =  $this->modelVault->load($vaultId);
            $dataKey = $vault->getDataKey();
        }

        return [
            'type'              => $this->_requestType,
            'data_key'          => $dataKey,
            'order_id'          => $this->generateUniqueOrderId(),
            'cust_id'           => $this->getCustomerId(),
            'amount'            => $this->getAmount(),
            'cavv'              => $this->getCavv(),
            self::CRYPT_FIELD   => 1
        ];
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
        
        $payment = $this->getPayment();
        $methodCode = $payment->getMethodInstance()->getCode();
        $storeId = $this->getHelper()->getConfigData("payment/".$methodCode."/login", true);
        $apiToken =  $this->getHelper()->getConfigData("payment/".$methodCode."/password", true);
        
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
     * @throws Exception if payment billing data is invalid
     * @param array $txnArray
     * @return Moneris_MpgTransaction
     */
    public function buildMpgTransaction($txnArray)
    {
        $mpgTxn = new \mpgTransaction($txnArray);
        $payment = $this->getPayment();
    
        $mpgCustInfo = $this->buildMpgCustInfo($payment->getOrder());
        if ($mpgCustInfo) {
            $mpgTxn->setCustInfo($mpgCustInfo);
        }
    
        $mpgAvsInfo = $this->buildMpgAvsInfo($payment->getOrder()->getBillingAddress());
        if ($mpgAvsInfo) {
            $mpgTxn->setAvsInfo($mpgAvsInfo);
        }
    
        $mpgCvdInfo = $this->buildMpgCvdInfo($payment);
        if ($mpgCvdInfo) {
            $mpgTxn->setCvdInfo($mpgCvdInfo);
        }
    
        return $mpgTxn;
    }
}
