<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Model\ResourceModel\Vault;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'vault_id';

    protected function _construct()
    {
        $this->_init('Moneris\CreditCard\Model\Vault', 'Moneris\CreditCard\Model\ResourceModel\Vault');
    }
}
