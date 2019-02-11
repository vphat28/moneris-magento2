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
class Completion extends Transaction
{
    protected $_requestType = self::COMPLETION;

    public function buildTransactionArray()
    {
        $payment = $this->getPayment();

        if (!$payment) {
            return [];
        }

        $this->_requestType = $this->getUpdatedRequestType();
        $receiptId = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'receipt_id');
        $monerisOrderId = ($receiptId)? $receiptId : $payment->getLastTransId();

        return [
            'type'              => $this->_requestType,
            'order_id'          => $monerisOrderId,
            'comp_amount'       => $this->getAmount(),
            self::CRYPT_FIELD   => $this->getCryptType(),
            'txn_number'        => $payment->getCcTransId()
        ];
    }
}
