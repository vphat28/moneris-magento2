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
class ReAuth extends Transaction
{
    protected $_requestType = 'reauth';

    public function buildTransactionArray()
    {
        $payment = $this->getPayment();

        if (!$payment) {
            return [];
        }

        $this->_requestType = $this->getUpdatedRequestType();
        $receiptId = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'receipt_id');
        $monerisOrderId = ($receiptId)? $receiptId : $payment->getLastTransId();
        
        $transactions = $this->getOrderTransactions($payment->getOrder()->getId());
        $orig_order_id = '';
        $txn_number = '';
        foreach ($transactions as $transaction) {
            if ($transaction->getData('txn_type') == 'authorization') {
                $orig_order_id = $transaction->getAdditionalInformation('orig_order_id'); 
                $monerisOrderId .= '-'.time();
                $txn_number = $transaction->getData('txn_id');
                break;
            }
        }

        return [
            'type'              => $this->_requestType,
            'order_id'          => $monerisOrderId,
            'orig_order_id'     => $orig_order_id,
            'amount'            => $this->getAmount(),
            self::CRYPT_FIELD   => $this->getCryptType(),
            'txn_number'        => $txn_number,
            'crypt_type'        => 7
        ];
    }
}
