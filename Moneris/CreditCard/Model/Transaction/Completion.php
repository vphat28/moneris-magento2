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

        $order = $payment->getOrder();
        $currencyCode = $order->getOrderCurrencyCode();
        $custId = $order->getIncrementId();

        if (!$payment) {
            return [];
        }

        $this->_requestType = $this->getUpdatedRequestType();
        $receiptId = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'receipt_id');
        $monerisOrderId = ($receiptId)? $receiptId : $payment->getLastTransId();

        return [
            'type'              => $this->_requestType,
            'order_id'          => $monerisOrderId,
            'cust_id'       => $custId,
            'comp_amount'       => intval($this->getAmount()),
            'cardholder_amount'       => intval($this->getAmount()),
            'mcp_rate_token'       => '',
            'mcp_version'   => Transaction::MCP_VERSION,
//            'cardholder_currency_code' => $this->getIso4217Code($currencyCode),
            'cardholder_currency_code' => '826',
            self::CRYPT_FIELD   => '7',
            'txn_number'        => $payment->getCcTransId()
        ];
    }
}
