<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Block\Adminhtml;

class RecurringListing extends \Magento\Backend\Block\Widget\Grid\Container
{

    protected function _construct()
    {
        $this->_controller = 'adminhtml_post';
        $this->_blockGroup = 'Moneris_CreditCard';
        $this->_headerText = __('Recurring Payment');
        $this->setData(self::PARAM_BUTTON_NEW, false);
        parent::_construct();
    }
}
