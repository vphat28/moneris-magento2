<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Mycards;

use Moneris\CreditCard\Controller\AbstractMycards;
use Moneris\CreditCard\Helper\Data as chHelper;
use Moneris\CreditCard\Model\VaultSaveService;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Data\Form\FormKey\Validator;

class Save extends AbstractMycards
{
    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * Save constructor.
     * @param Context $context
     * @param chHelper $chHelper
     * @param VaultSaveService $vaultSaveService
     * @param SessionManagerInterface $customerSession
     * @param PageFactory $pageFactory
     * @param Registry $registry
     * @param Validator $formKeyValidator
     */
    public function __construct(Context $context, chHelper $chHelper, VaultSaveService $vaultSaveService, SessionManagerInterface $customerSession, PageFactory $pageFactory, Registry $registry, Validator $formKeyValidator)
    {
        parent::__construct($context, $chHelper, $vaultSaveService, $customerSession, $pageFactory, $registry);

        $this->formKeyValidator = $formKeyValidator;
    }

    protected function _execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $request = $this->getRequest();
        $data = $request->getParams();
        $this->vaultSaveService->process($data);
        $this->messageManager->addSuccessMessage(__('Your Credit Card has been saved successfully.'));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
