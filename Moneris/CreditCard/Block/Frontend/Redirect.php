<?php
namespace Moneris\CreditCard\Block\Frontend;

class Redirect extends AbstractBlock
{
    protected function getRedirectForm()
    {
        return $this->checkoutSession->getMonerisccMpiForm();
    }
}
