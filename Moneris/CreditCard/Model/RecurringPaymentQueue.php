<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Moneris\CreditCard\Model;

use Moneris\CreditCard\Model\ResourceModel\RecurringPaymentQueue as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class RecurringPaymentQueue extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}