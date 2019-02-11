<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Interac;

use Magento\Framework\Controller\ResultFactory;

class Referrer extends \Moneris\CreditCard\Controller\Interac
{
    public function execute()
    {
        $paymentData = $this->getPaymentData();
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($paymentData);
        return $resultJson;
    }
}
