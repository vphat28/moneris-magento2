<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

class AuthorizationRequest implements BuilderInterface
{
    
    /**
     * @var Session
     */
    private $session;

    /**
     * RequestDataBuilder constructor.
     * @param SessionManagerInterface $session
     */
    public function __construct(
        \Magento\Checkout\Model\Session $session
    ) {
        $this->session = $session;
    }
    
    /**
     * Builds request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $quote = $this->session->getQuote();
        $payment = $buildSubject['payment']->getPayment();
        $txnArray = [
            'type' => 'vdotme_preauth',
            'order_id' => $quote->reserveOrderId()->getReservedOrderId(),
            'amount' => $this->formatAmount($quote->getGrandTotal()),
            'callid' => $payment->getAdditionalInformation("callId"),
            'crypt_type' => 7,
        ];
        return (array) $txnArray;
    }
    
    /**
     * @param float $amount
     * @return string
     */
    private function formatAmount($amount)
    {
        if (!is_float($amount)) {
            $amount = (float) $amount;
        }
        
        return number_format($amount, 2, '.', '');
    }
}
