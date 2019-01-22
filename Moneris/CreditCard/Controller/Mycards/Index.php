<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Mycards;

use Moneris\CreditCard\Controller\AbstractMycards;
use Magento\Framework\App\ResponseInterface;

class Index extends AbstractMycards
{
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    protected function _execute()
    {
        if ($this->customerSession->isLoggedIn()) {
            $resultPage = $this->pageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('My Credit Cards (Moneris)'));
            return $resultPage;
        } else {
            $this->customerSession->setAfterAuthUrl($this->urlBuilder->getUrl('moneriscc/mycards'));
            $this->_redirect('customer/account/login');
        }
    }
}
