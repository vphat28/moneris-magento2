<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Index;

use Magento\Framework\App\Action\Context;
use Moneris\CreditCard\Helper\Data;
use Magento\Framework\Controller\ResultFactory;

class Loadredirect extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * Loadredirect constructor.
     * @param Context $context
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Data $helper
    ) {
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $paymentData = $this->helper->getPaymentData();
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($paymentData);
        return $resultJson;
    }
}
