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
class VoidTransaction extends Transaction
{
    protected $_requestType = self::PURCHASE_CORRECTION;

    public function buildTransactionArray()
    {
        $payment = $this->getPayment();

        if (!$payment) {
            return [];
        }

        $cryptType = $this->getCryptType();
        if (!$cryptType) {
            $cryptType = $payment->getCryptType();
        }

        $this->_requestType = $this->getUpdatedRequestType();
        $receiptId = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'receipt_id');
        $monerisOrderId = ($receiptId)? $receiptId : $payment->getLastTransId();

        return [
            'type'              => $this->_requestType,
            'order_id'          => $monerisOrderId,
            'crypt_type'        => $this->getCryptType() ? $this->getCryptType() : self::CRYPT_SEVEN,
            'txn_number'        => $payment->getCcTransId(),
            //'cust_id'           => $payment->getOrder()->getCustomerId()
        ];
    }
}
