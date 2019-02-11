<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Transaction\Interac;

use Moneris\CreditCard\Model\Transaction;
use Magento\Framework\DataObject;

class Completion extends Transaction
{
    protected $_requestType = "idebit_purchase";

    public function buildTransactionArray()
    {
        $payment = $this->getPayment();

        if (!$payment) {
            return [];
        }

        $dataDebit = ($this->getDataDebit())?$this->getDataDebit():$payment->getCcTransId();

        return [
            'type'              => $this->_requestType,
            'order_id'          => $this->generateUniqueOrderId(),
            'cust_id'           => $this->getCustomerId(),
            'amount'            => $this->getAmount(),
            'idebit_track2'     => $dataDebit
        ];
    }

    public function buildMpgTransaction($txnArray)
    {
        $mpgTxn = parent::buildMpgTransaction($txnArray);
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
