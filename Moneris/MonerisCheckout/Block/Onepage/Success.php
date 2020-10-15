<?php

namespace Moneris\MonerisCheckout\Block\Onepage;

use Magento\Framework\View\Element\Template;

class Success extends Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct(
        Template\Context $context,
        \Magento\Checkout\Model\Session $session,
        array $data = [] ) {
        $this->_checkoutSession = $session;
        parent::__construct( $context, $data );
    }

    public function getMonerisData()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();

        if ($payment->getMethod() === 'chmoneriscc') {
            return $payment->getAdditionalInformation();
        }

        return [];
    }
}
