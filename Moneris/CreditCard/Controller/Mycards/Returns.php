<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Mycards;

use Moneris\CreditCard\Controller\AbstractMycards;

class Returns extends AbstractMycards
{
    protected function _execute()
    {
        $request = $this->getRequest();
        $data = $request->getParams();
        if (isset($data['cancel']) && $data['cancel']) {
            $this->messageManager->addNotice(__('Cancelled by cardholder.'));
        } else {
            $this->vaultSaveService->addVault($data);
            $this->messageManager->addSuccessMessage(__('Your Credit Card has been saved successfully.'));
        }
        
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
