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

class Edit extends AbstractMycards
{
    /**
     * @var \Moneris\CreditCard\Model\Vault
     */
    private $vault;

    /**
     * Edit constructor.
     * @param Context $context
     * @param chHelper $chHelper
     * @param VaultSaveService $vaultSaveService
     * @param SessionManagerInterface $customerSession
     * @param PageFactory $pageFactory
     * @param Registry $registry
     * @param \Moneris\CreditCard\Model\Vault $vault
     */
    public function __construct(
        Context $context,
        chHelper $chHelper,
        VaultSaveService $vaultSaveService,
        SessionManagerInterface $customerSession,
        PageFactory $pageFactory,
        Registry $registry,
        \Moneris\CreditCard\Model\Vault $vault
    ) {
        parent::__construct(
            $context,
            $chHelper,
            $vaultSaveService,
            $customerSession,
            $pageFactory,
            $registry
        );

        $this->vault = $vault;
    }

    protected function _execute()
    {
        $token = null;
        if ($this->getRequest()->getParam('id')) {
            $token = $this->vault->load($this->getTokenId());
        }
        
        $this->registry->register('current_token', $token);
        $resultPage = $this->pageFactory->create();
        $resultPage->getConfig()->getTitle()->set($token ? __('Edit Credit Card') : __('New Credit Card'));
        return $resultPage;
    }
}
