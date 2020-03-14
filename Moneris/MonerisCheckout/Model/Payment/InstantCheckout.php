<?php

namespace Moneris\MonerisCheckout\Model\Payment;

use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;

class InstantCheckout extends Adapter
{
    const METHOD_CODE = 'monerisinstantcheckout';

    public function capture(InfoInterface $payment, $amount)
    {
        return $this;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        return $this;
    }
}
