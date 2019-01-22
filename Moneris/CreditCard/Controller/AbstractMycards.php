<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Controller;

use Moneris\CreditCard\Model\Vault;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Moneris\CreditCard\Model\VaultSaveService;
use Moneris\CreditCard\Helper\Data as chHelper;

abstract class AbstractMycards extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @var PageFactory
     */
    public $pageFactory;

    /**
     * @var VaultSaveService
     */
    public $vaultSaveService;
    
    /**
     * @var Registry
     */
    public $registry;
    
    /**
     * @var chHelper
     */
    public $chHelper;
    
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * AbstractMycards constructor.
     * @param Context $context
     * @param chHelper $chHelper
     * @param VaultSaveService $vaultSaveService
     * @param SessionManagerInterface $customerSession
     * @param PageFactory $pageFactory
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        chHelper $chHelper,
        VaultSaveService $vaultSaveService,
        SessionManagerInterface $customerSession,
        PageFactory $pageFactory,
        Registry $registry
    ) {
        $this->chHelper = $chHelper;
        $this->vaultSaveService = $vaultSaveService;
        parent::__construct($context);
        $this->urlBuilder = $context->getUrl();
        $this->customerSession = $customerSession;
        $this->pageFactory = $pageFactory;
        $this->registry = $registry;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            if ($this->customerSession->isLoggedIn()) {
                if ($this->chHelper->getConfigData('payment/chmoneris/enable_vault')) {
                    return $this->_execute();
                } else {
                    $this->_redirect('customer/account');
                }
            } else {
                $this->customerSession->setAfterAuthUrl($this->urlBuilder->getUrl('moneriscc/mycards'));
                $this->_redirect('customer/account/login');
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
    }

    public function getTokenId()
    {
        $id = $this->getRequest()->getParam('id');
        if (!is_numeric($id)) {
            throw new PaymentException(__('Invalid token id'));
        }
        
        return (int) $id;
    }

    abstract protected function _execute();
}
