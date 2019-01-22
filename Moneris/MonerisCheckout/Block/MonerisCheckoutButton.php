<?php

namespace Moneris\MonerisCheckout\Block;

use Magento\Framework\View\Element\Template;

class MonerisCheckoutButton extends Template
{
    public function __construct(Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }
}