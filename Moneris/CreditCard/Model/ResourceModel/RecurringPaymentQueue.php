<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RecurringPaymentQueue extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('collinsharper_moneris_payment_queue', 'entity_id');
    }
}