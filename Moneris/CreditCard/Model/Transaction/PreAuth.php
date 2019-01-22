<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Transaction;

use Moneris\CreditCard\Model\Mpg\MpgCof;
use Moneris\CreditCard\Model\Transaction;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\Payment;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PreAuth extends Transaction
{
    protected $_requestType = self::PREAUTH;
    protected $_isVoidable  = true;

    protected $_canUseAvsCvd = true;
    /** @var Payment */
    public $payment;

    public function buildTransactionArray()
    {
        $payment = $this->getPayment();
        
        if (!$payment) {
            return [];
        }

        $this->_requestType = $this->getUpdatedRequestType();

        $type = 'res_preauth_cc';
        if ($payment->getAdditionalInformation('payment_type') &&
            $payment->getAdditionalInformation('payment_type') == 'authorize_capture'
        ) {
            $type = 'res_purchase_cc';
        }

        if (!empty($payment->getAdditionalInformation('data_key'))) {
            $dataKey = $payment->getAdditionalInformation('data_key');
            return [
                'type'          => $type,
                'data_key'      => $dataKey,
                'order_id'      => $this->generateUniqueOrderId(),
                'cust_id'       => $this->getCustomerId(),
                'amount'        => $this->getAmount(),
                self::CRYPT_FIELD   => self::CRYPT_SEVEN
            ];
        }

        if ($payment->getAdditionalInformation('vault_id') &&
            is_numeric($payment->getAdditionalInformation('vault_id')) &&
            !$payment->getCcNumber()
        ) {
            $vaultId = $payment->getAdditionalInformation('vault_id');
            $vault =  $this->modelVault->load($vaultId);
            $dataKey = $vault->getDataKey();

            if (!empty($payment->getAdditionalInformation('recurring') == 1)) {
                $payment->setAdditionalInformation('data_key', $dataKey);
            }

            return [
                'type'          => $type,
                'data_key'      => $dataKey,
                'order_id'      => $this->generateUniqueOrderId(),
                'cust_id'       => $this->getCustomerId(),
                'amount'        => $this->getAmount(),
                self::CRYPT_FIELD   => $this->getCryptType()
            ];
        }

        $ccNumber = $payment->getCcNumber();
        if (!$payment->getCcNumber() || $payment->getCcNumber() == '') {
            $ccNumber = $this->getHelper()->getCheckoutSession()->getMonerisccCcNumber();
        }

        return [
            'type'          => $this->_requestType,
            'order_id'      => $this->generateUniqueOrderId(),
            'cust_id'       => $this->getCustomerId(),
            'amount'        => $this->getAmount(),
            'pan'           => $ccNumber,
            'expdate'       => $this->getFormattedExpiry($payment),
            self::CRYPT_FIELD   => $this->getCryptType()
        ];
    }



    /**
     * @throws Exception if payment billing data is invalid
     * @param array $txnArray
     * @return Moneris_MpgTransaction
     */
    public function buildMpgTransaction($txnArray)
    {
        /** @var \mpgTransaction $mpgTxn */
        $mpgTxn = parent::buildMpgTransaction($txnArray);

        /** @var Payment $payment */
        $payment = $this->getPayment();

        $mpgCustInfo = $this->buildMpgCustInfo($payment->getOrder());
        if ($mpgCustInfo) {
            $mpgTxn->setCustInfo($mpgCustInfo);
        }

        $cof = new MpgCof();
        $cof->setPaymentIndicator(MpgCof::PAYMENT_INDICATOR_UNSCHEDULE);
        $cof->setPaymentInformation(MpgCof::PAYMENT_INFO_FIRST_TXN);

        if ($this->payment->getAdditionalInformation('vault_id')) {
            $cof->setIssuerId($this->modelVault->getData('issuer_id'));
        }

        if (!empty($payment->getAdditionalInformation('vault_id'))) {
            $cof->setPaymentIndicator(MpgCof::PAYMENT_INDICATOR_SUBSEQUENT);
            $cof->setPaymentInformation(MpgCof::PAYMENT_INFO_SUB_TXN);
        }

        $mpgTxn->cof = $cof;

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
