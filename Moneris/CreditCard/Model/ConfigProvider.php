<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Moneris\CreditCard\Model;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Payment\Model\CcGenericConfigProvider;
use Magento\Payment\Model\CcConfig;

/**
 * ConfigProvider Class
 */
class ConfigProvider extends CcGenericConfigProvider
{
    const CC_VAULT_CODE = 'bchmoneriscc_vault';
    /**
     * @var string
     */
    public $code = 'chmoneriscc';

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Moneris\CreditCard\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    private $paymentHelper;

    /**
     * @var \Magento\Payment\Model\Config
     */
    private $paymentConfig;
    
    /**
     * @var \Moneris\CreditCard\Model\Vault
     */
    private $modelVault;
    
    /**
     *
     * @var \Magento\Framework\View\Asset\Repository 
     */
    private $asset;

    /**
     * @param CcConfig $ccConfig
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param SessionManagerInterface $checkoutSession
     * @param SessionManagerInterface $customerSession
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Moneris\CreditCard\Helper\Data $dataHelper
     * @param array $methodCodes
     */
    public function __construct(
        CcConfig $ccConfig,
        \Magento\Payment\Helper\Data $paymentHelper,
        SessionManagerInterface $checkoutSession,
        SessionManagerInterface $customerSession,
        \Magento\Payment\Model\Config $paymentConfig,
        \Moneris\CreditCard\Helper\Data $dataHelper,
        \Moneris\CreditCard\Model\Vault $modelVault,
        \Magento\Framework\View\Asset\Repository $asset,
        array $methodCodes = []
    ) {
        $this->paymentHelper    = $paymentHelper;
        $this->checkoutSession  = $checkoutSession;
        $this->customerSession  = $customerSession;
        $this->dataHelper       = $dataHelper;
        $this->modelVault       = $modelVault;
        $this->paymentConfig    = $paymentConfig;
        $this->asset            = $asset;

        parent::__construct($ccConfig, $paymentHelper, [$this->code]);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $config = parent::getConfig();
        $config['payment'][$this->code]['active'] = true;
        $config['payment'][$this->code]['isActive'] = true;
        $config['payment'][$this->code]['ccVaultCode'] = self::CC_VAULT_CODE;
        $config['payment'][$this->code]['isRecurring'] = (bool)$this->dataHelper->getConfigData('payment/chmoneriscc/is_recurring');
        $config['payment'][$this->code]['supportedRecurringTerms'] = $this->getSupportedRecurringTerms();
        $config['payment'][$this->code]['storedCards'] = $this->getStoredCards();
        $config['payment'][$this->code]['canSaveCard'] = $this->canSaveCard();
        $config['payment'][$this->code]['redirectAfterPlaceOrder'] = $this->redirectAfterPlaceOrder();
        $config['payment'][$this->code]['forceSaveCard'] = false;
        
        $config['payment']['chmonerisredirect']['storedCards'] = $this->getStoredCards();
        $config['payment']['chmonerisredirect']['canSaveCard'] = $this->canUseCardForHosted();
        $config['payment']['chmonerisredirect']['redirectAfterPlaceOrder'] = $this->redirectAfterPlaceOrder(
            'chmonerisredirect'
        );    
        $range = range(date('Y'), date('Y') + 15);
        $config['payment']['ccform']['years']['chmoneriscc'] = array_combine($range, $range);

        return $config;
    }

    protected function getSupportedRecurringTerms()
    {
        $terms = explode(',', $this->dataHelper->getConfigData('payment/' . $this->code . '/recurring_term'));
        $formatted = [];

        foreach ($terms as $k => $v) {
            if ($v == 'monthly') {
                $formatted[$v] = __('Monthly');
            } if ($v == 'yearly') {
                $formatted[$v] = __('Yearly');
            } if ($v == 'daily') {
                $formatted[$v] = __('Daily');
            } if ($v == 'weekly') {
                $formatted[$v] = __('Weekly');
            }
        }

        return $formatted;
    }

    /**
     * @param string $menthod
     * @return bool
     */
    public function redirectAfterPlaceOrder($menthod = 'chmoneriscc')
    {
        if ($this->dataHelper->getConfigData('payment/'.$menthod.'/vbv_enabled')) {
            return false;
        }
        
        return true;
    }

    /**
     * @return bool
     */
    public function canUseCardForHosted()
    {
        if ($this->customerSession->isLoggedIn() && $this->canUseVault()) {
            return false;
        }
    
        return true;
    }

    /**
     * @return bool
     */
    public function canSaveCard()
    {
        if ($this->customerSession->isLoggedIn() && $this->canUseVault()) {
            return true;
        }
        
        return false;
    }

    /**
     * @return bool
     */
    public function canUseVault()
    {
        if ($this->dataHelper->getConfigData('payment/chmoneris/enable_vault')) {
            return true;
        }
    
        return false;
    }
    /**
     * Returns applicable stored cards
     *
     * @return array
     */
    public function getStoredCards()
    {
        $cardList = [];
        $cardAvailable = $this->dataHelper->getConfigData('payment/chmoneriscc/cctypes');
        if ($this->customerSession->isLoggedIn() && $this->canUseVault()) {
            $customerId = $this->customerSession->getId();
            $cards = $this->modelVault->getCollection();
            $cards->addFieldToFilter('customer_id', $customerId);
            if ($cardAvailable) {
                $cards->addFieldToFilter('card_type', ['in' => $cardAvailable]);
            }
            
            $cards->setOrder(
                'created_date',
                'desc'
            );
            if ($cards->getSize()>0) {
                foreach ($cards as $key => $card) {
                    $cardList[] = [
                        'id' => $card->getVaultId(),
                        'label' => $card->getCcLast()
                    ];
                }
            }
        }
        
        return $cardList;
    }
}
