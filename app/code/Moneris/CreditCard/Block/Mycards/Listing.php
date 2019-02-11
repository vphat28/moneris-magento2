<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Block\Mycards;

use Moneris\CreditCard\Model\Vault;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Session;
use Moneris\CreditCard\Helper\Data as ChHelper;

class Listing extends \Magento\Framework\View\Element\Template
{
    /** @codingStandardsIgnoreLine @var ManagerInterface */
    protected $tokenManager;

    /**
     * @codingStandardsIgnoreLine @var Session
     */
    protected $customerSession;

    /**
     * @codingStandardsIgnoreLine @var mixed
     */
    protected $curPage;

    /**
     * @codingStandardsIgnoreLine @var ChHelper
     */
    protected $chHelper;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @codingStandardsIgnoreLine @var array
     */
    protected $cardTypeTranslationMap = [
        'AE'    => 'American Express',
        'DI'    => 'Discover',
        'DN'    => 'Diners Club',
        'JCB'   => 'JCB',
        'MC'    => 'MasterCard',
        'VI'    => 'Visa',
        'OT'    => 'Other'
    ];

    /**
     * Listing constructor.
     * @param Template\Context $context
     * @param Vault $tokenManager
     * @param ChHelper $chHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Vault $tokenManager,
        ChHelper $chHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->chHelper = $chHelper;
        $this->curPage = $this->getRequest()->getParam('p');
        $this->tokenManager = $tokenManager;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }

    public function getActiveTokensList()
    {
        $collection = $this->tokenManager->getCollection();
        $collection->addFieldToFilter('customer_id', $this->customerSession->get());
        $collection->setOrder(
            'created_date',
            'desc'
        );
        $collection->setPageSize(30);
        if ($this->curPage) {
            $collection->setCurPage($this->curPage);
        }
        
        $collection->load();
        return $collection;
    }

    public function getDefaultToken()
    {
        return $this->tokenManager->getDefaultToken();
    }

    public function getEditTokenUrl($id)
    {
        return $this->_urlBuilder->getUrl('*/*/edit', ['id' => $id]);
    }
    
    public function getEditHostedTokenUrl($id)
    {
        return $this->_urlBuilder->getUrl('*/*/edit', ['id' => $id, 'hosted' => true]);
    }
    
    public function getCreateTokenUrl()
    {
        return $this->_urlBuilder->getUrl('*/*/create');
    }

    public function getDeleteTokenUrl($id)
    {
        return $this->_urlBuilder->getUrl('*/*/delete', ['id' => $id]);
    }

    public function getSetDefaultTokenUrl($id)
    {
        return $this->_urlBuilder->getUrl('*/*/setDefault', ['id' => $id]);
    }
    
    public function getMapCardType($type)
    {
        $arrayType = $this->cardTypeTranslationMap;
        if (array_key_exists($type, $arrayType)) {
            return $arrayType[$type];
        }
        
        return $type;
    }

    public function getCustomerData()
    {
        $customerData = $this->customerRepository->getById($this->customerSession->getId());
        return $customerData;
    }
    
    public function getHelper()
    {
        return $this->chHelper;
    }
    
    public function isHostedVault()
    {
        return $this->getHelper()->getConfigData('payment/chmoneris/hosted_vault');
    }
    
    public function getHostedVaultResId()
    {
        return $this->getHelper()->getConfigData('payment/chmoneris/res_id', true);
    }
    
    public function getHostedVaultResKey()
    {
        return $this->getHelper()->getConfigData('payment/chmoneris/res_key', true);
    }
}
