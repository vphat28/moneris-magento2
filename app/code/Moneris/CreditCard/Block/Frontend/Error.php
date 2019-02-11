<?php
namespace Moneris\CreditCard\Block\Frontend;

class Error extends AbstractBlock
{
    public function getResponseError()
    {
        return $this->checkoutSession->getResponseError();
    }

    public function getCheckoutCartUrl()
    {
        return $this->getCheckoutHelper()->getUrl('checkout/cart');
    }
}
