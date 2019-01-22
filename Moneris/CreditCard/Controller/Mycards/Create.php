<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Mycards;

use Moneris\CreditCard\Controller\AbstractMycards;

class Create extends AbstractMycards
{
    protected function _execute()
    {
        $this->_forward('edit');
    }
}
