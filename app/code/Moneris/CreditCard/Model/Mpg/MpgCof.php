<?php

namespace Moneris\CreditCard\Model\Mpg;

class MpgCof
{
    const PAYMENT_INDICATOR_UNSCHEDULE = 'C';
    const PAYMENT_INDICATOR_SUBSEQUENT = 'R';
    const PAYMENT_INFO_FIRST_TXN = 0;
    const PAYMENT_INFO_SUB_TXN = 2;

    var $payment_indicator = null;
    var $payment_information = null;
    var $issuer_id = null;

    public function setPaymentIndicator($v)
    {
        $this->payment_indicator = $v;
    }

    public function setPaymentInformation($v)
    {
        $this->payment_information = $v;
    }

    public function setIssuerId($v)
    {
        $this->issuer_id = $v;
    }
}