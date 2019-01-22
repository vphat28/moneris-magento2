<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Moneris\CreditCard\Model\ResourceModel\RecurringPayment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Moneris\CreditCard\Model\RecurringPayment as Model;
use Moneris\CreditCard\Model\ResourceModel\RecurringPayment as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * Specify model and resource model for collection
     */
    protected function _construct()
    {
        $this->_init(
            Model::class,
            ResourceModel::class
        );
    }
}