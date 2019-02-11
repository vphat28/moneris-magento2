<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Block\Adminhtml;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\Config;

class Listing extends \Magento\Payment\Block\Adminhtml\Transparent\Form
{
    /**
     * @var \Moneris\CreditCard\Model\Vault
     */
    private $vaultModel;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $quoteSession;

    /**
     * @var \Moneris\CreditCard\Helper\Data
     */
    private $chHelper;

    /**
     * Listing constructor.
     * @param Context $context
     * @param Config $paymentConfig
     * @param SessionManagerInterface $checkoutSession
     * @param \Moneris\CreditCard\Model\Vault $vault
     * @param \Magento\Backend\Model\Session\Quote $quote
     * @param \Moneris\CreditCard\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $paymentConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Moneris\CreditCard\Model\Vault $vault,
        \Magento\Backend\Model\Session\Quote $quote,
        \Moneris\CreditCard\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $checkoutSession, $data);

        $this->vaultModel = $vault;
        $this->quoteSession = $quote;
        $this->chHelper = $helper;
    }

    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection|string
     */
    public function getActiveTokensList()
    {
        $collection = $this->vaultModel->getCollection();
        $customerId = $this->quoteSession->getCustomerId();
        $cardAvailable = $this->chHelper->getConfigData('payment/chmoneriscc/cctypes');
        if ($cardAvailable) {
            $collection->addFieldToFilter('card_type', ['in' => $cardAvailable]);
        }
        
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->setOrder(
            'created_date',
            'desc'
        );
        $collection->setPageSize(10);
        $collection->load();
        if (!$collection->getSize()) {
            $collection = '';
        }
        
        return $collection;
    }
}
