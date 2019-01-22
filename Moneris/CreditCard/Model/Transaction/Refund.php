<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Transaction;

use Moneris\CreditCard\Model\Transaction;
use Magento\Framework\DataObject;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Refund extends Transaction
{
    protected $_requestType = self::REFUND;

    public function buildTransactionArray()
    {
        $payment = $this->getPayment();

        if (!$payment) {
            return [];
        }

        $this->_requestType = $this->getUpdatedRequestType();
        $receiptId = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'receipt_id');
        $monerisOrderId = ($receiptId)? $receiptId : $payment->getLastTransId();
        
        $requestType = $this->_requestType;
        $paymentType = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'trans_name');
        if ($paymentType && strpos($paymentType, 'idebit_purchase') !== false) {
            $requestType = "idebit_refund";
        }

        return [
            'type'          => $requestType,
            'order_id'      => $monerisOrderId,
            'amount'        => $this->getAmount(),
            'crypt_type'    => $this->getCryptType() ? $this->getCryptType() : self::CRYPT_FIVE,
            'txn_number'    => $payment->getCcTransId()
        ];
    }
}
