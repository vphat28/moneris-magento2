<?php

namespace Moneris\MonerisCheckout\Plugin;

use Moneris\MonerisCheckout\Helper\Data;

class OverrideOnepageCheckout
{
    /** @var Data */
    private $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function afterCanOnepageCheckout($subject, $return)
    {
        if ($this->helper->isActive()) {
            return false;
        }

        return $return;
    }
}
