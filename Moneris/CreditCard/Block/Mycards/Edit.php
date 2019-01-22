<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Block\Mycards;

use Moneris\CreditCard\Model\Config\ApiConfigProvider2;
use Moneris\CreditCard\Model\Vault;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Element\Template;

class Edit extends \Magento\Customer\Block\Address\Edit
{
    /**
     * @var
     */
    protected $_scopeConfig;

    /**
     * @var mixed
     */
    public $tokenId;

    /**
     * @var Vault
     */
    public $token;

    /**
     * @var \Moneris\CreditCard\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Payment\Block\Form\Cc
     */
    protected $ccBlock;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    private $paymentHelper;

    /**
     * Edit constructor.
     * @param Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param SessionManagerInterface $customerSession
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Moneris\CreditCard\Model\Vault $vault
     * @param \Moneris\CreditCard\Helper\Data $helper
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Moneris\CreditCard\Model\Vault $vault,
        \Moneris\CreditCard\Helper\Data $helper,
        \Magento\Payment\Helper\Data $paymentHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $customerSession,
            $addressRepository,
            $addressDataFactory,
            $currentCustomer,
            $dataObjectHelper,
            $data
        );

        $this->_scopeConfig = $context->getScopeConfig();
        $this->token = $vault;
        if ($this->_request->getParam('id')) {
            $this->tokenId = $this->_request->getParam('id');
            $this->token = $vault->load($this->tokenId);
        }
        $this->helper = $helper;
        $this->paymentHelper = $paymentHelper;
    }

    public function getSubmitUrl()
    {
        return $this->getUrl('*/*/save');
    }

    public function getSaveUrl()
    {
        return $this->_urlBuilder->getUrl('*/*/save');
    }

    public function getTitle()
    {
        $title = __('Create New Card');
        if ($this->tokenId) {
            $title = __('Update Card Information');
        }
        
        return $title;
    }

    public function getUseIframe()
    {
        return $this->_scopeConfig->getValue(
            'payment/chcybersource/use_iframe',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return \Magento\Payment\Block\Form\Cc
     */
    public function getCcBlock()
    {
        if ($this->ccBlock === null) {
            $this->ccBlock = $this->getLayout()->createBlock('Magento\Payment\Block\Form\Cc');
            $this->ccBlock->setMethod($this->getMethod());
        }
        
        return $this->ccBlock;
    }
    /**
     * Get the active payment method.
     *
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getMethod()
    {
        return $this->paymentHelper->getMethodInstance('chmoneriscc');
    }

    public function getAdditional($key)
    {
        if ($this->token->getVaultId()) {
            return $this->token->getData($key);
        }
        
        return null;
    }
    
    public function getHelper()
    {
        return $this->helper;
    }
    
    public function isHostedVault()
    {
        if ($this->getHelper()->getConfigData('payment/chmoneris/hosted_vault')
            && $this->_request->getParam('hosted')
        ) {
            return true;
        }
        
        return false;
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
