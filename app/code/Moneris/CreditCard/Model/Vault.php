<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Moneris\CreditCard\Model;

class Vault extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Moneris\CreditCard\Model\ResourceModel\Vault');
    }
}
