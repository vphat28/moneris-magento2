<?php

namespace Moneris\MonerisCheckout\Plugin;

class Directory
{
    public function afterIsRegionRequired($subject, $return)
    {
        return false;
    }
}
