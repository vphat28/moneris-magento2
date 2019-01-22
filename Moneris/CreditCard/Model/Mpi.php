<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model;

use Moneris\CreditCard\Model\AbstractModel;
use Magento\Framework\DataObject;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Mpi extends AbstractModel
{

    public function buildTransactionArray()
    {
        throw new \Exception('This should be overridden in the extending class.');
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
        
        $mpiHttpsPost = new \mpgHttpsPost($storeId, $apiToken, $mpgRequest);
        $mpiResponse = $mpiHttpsPost->getMpgResponse();

        return $mpiResponse;
    }
}
