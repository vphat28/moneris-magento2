<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Transaction\Cavv;

use Moneris\CreditCard\Model\Transaction\PreAuth as TransactionPreAuth;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PreAuth extends TransactionPreAuth
{
    protected $_requestType = self::CAVV_PREAUTH;

    public function buildTransactionArray()
    {
        $txnArray = parent::buildTransactionArray();

        if (empty($txnArray)) {
            return $txnArray;
        }

        unset($txnArray[self::CRYPT_FIELD]);
        $txnArray = array_merge(
            $txnArray,
            [self::CAVV_FIELD  => $this->getCavv()]
        );

        return $txnArray;
    }
}
