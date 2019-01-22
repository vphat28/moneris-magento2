<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Mpi;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Acs extends \Moneris\CreditCard\Model\Mpi
{
    public function buildTransactionArray()
    {
        $txnArray = [
            'type'  => self::ACS_TYPE,
            'PaRes' => $this->getPaRes(),
            'MD'    => $this->getMd()
        ];
        return $txnArray;
    }
}
