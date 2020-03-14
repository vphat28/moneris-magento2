<?php

namespace Moneris\MonerisCheckout\Plugin;

class OverrideOnepageCheckout
{
    public function afterCanOnepageCheckout($subject, $return)
    {
        return false;
    }
}
