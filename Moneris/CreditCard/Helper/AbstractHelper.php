<?php

/**
 * Copyright © 2015 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_TEST = 'test';
    const XML_PATH_DEBUG = 'debug';
    const CODE_PATH = 'app/code/CollinsHarper/';

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Moneris\CreditCard\Logger\Logger
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Moneris\CreditCard\Model\ObjectFactory
     */
    protected $objectFactory;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cacheManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var
     */
    protected $customerSession;

    /**
     * @var bool
     */
    private $isMock = false;

    /**
     * @var bool
     */
    private $mockConfig = false;

    /**
     * @var bool
     */
    private $mockObjectManager = false;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Moneris\CreditCard\Logger\Logger $logger
     * @param \Moneris\CreditCard\Model\ObjectFactory $objectFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\CacheInterface $cacheManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Moneris\CreditCard\Logger\Logger $logger,
        \Moneris\CreditCard\Model\ObjectFactory $objectFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\CacheInterface $cacheManager
    ) {
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->eventManager = $context->getEventManager();
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->objectFactory = $objectFactory;
        $this->scopeConfig = $context->getScopeConfig();

        parent::__construct($context);
    }

    public function isTest()
    {
        return $this->getModuleConfig(self::XML_PATH_TEST);
    }

    public function isDebug()
    {
        return $this->getModuleConfig(self::XML_PATH_DEBUG);
    }

    public function getConfigPath()
    {
        return self::CONFIG_PATH . self::MODULE_CODE . '/';
    }

    public function getModuleConfig($path)
    {
        $path = $this->getConfigPath() . $path;

        $value = $this->getConfigValue($path);

        if ($this->isAdmin()) {
            $_creditMemo = $this->registry->registry('current_creditmemo');
            if ($_creditMemo) {
                $storeId = $_creditMemo->getOrder()->getData("store_id");
            } else {
                $quote = $this->getBackendSessionQuote();

                $storeId = $quote->getStoreId();
            }

            if (!$storeId) {
                if ($this->registry->registry('current_order') &&
                    $this->registry->registry('current_order')->getStoreId()
                ) {
                    $storeId = $this->registry->registry('current_order')->getStoreId();
                } elseif ($this->registry->registry('current_invoice') &&
                    $this->registry->registry('current_invoice')->getStoreId()
                ) {
                    $storeId = $this->registry->registry('current_invoice')->getStoreId();
                }
            }

            if ($storeId) {
                $value = $this->getConfigValue($path, $storeId);
            }
        }

        if ($this->isMock()) {
            $value = isset($this->mockConfig[$path]) ? $this->mockConfig[$path]: $value;
        }

        return $value;
    }

    public function getObject($class)
    {
        if ($this->isMock() && $this->mockObjectManager) {
            return $this->mockObjectManager->getObject($class);
        }

        return $this->objectFactory->create([], $class);
    }

    public function setMockManager($objectManager)
    {
        $this->mockObjectManager = $objectManager;
        $this->isMock = true;
    }
    public function isAdmin()
    {
        /**
         * @TODO this needs testing && test for username? valid session?
         */
        return $this->getBackendSession() && $this->getBackendSession()->getId();
    }

    public function isMock()
    {
        return $this->isMock == true;
    }

    public function setMockData($data)
    {
        $this->mockConfig = $data;
        $this->isMock = true;
    }

    public function getMockData()
    {
        return $this->mockConfig;
    }

    /**
     *
     * @param string $path
     * @param string $scopeType
     * @param string $scopeCode
     * @return mixed
     */
    public function getConfigValue($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        $value = $this->scopeConfig->getValue($path, $scopeType, $scopeCode);

        if ($this->isMock()) {
            $value = isset($this->mockConfig[$path]) ? $this->mockConfig[$path]: $value;
        }
        return $value;
    }

    public function repopulateCart()
    {
        $session = $this->getCheckoutSession();
        $quoteId = $session->getMonerisccQuoteId(true);

        if (!$quoteId) {
            return $this;
        }

        $session->setQuoteId($quoteId);
        $session->setLoadInactive(true)->getQuote()->setIsActive(true)->save();

        return $this;
    }

    public function setCustomerSession($x)
    {
        $this->customerSession = $x;
    }
    public function getCustomerSession()
    {
        if (!$this->customerSession) {
            $this->customerSession = $this->getObject('Magento\Customer\Model\Session');
        }
        return $this->customerSession;
    }

    public function getCheckoutSession()
    {
        return $this->getObject('Magento\Checkout\Model\Session');
    }

    public function getOrder($orderId)
    {
        return $this->getObject('Magento\Sales\Model\Order')->load($orderId);
    }

    public function getBackendSession()
    {
        return $this->getObject('Magento\Backend\Model\Session');
    }

    public function getBackendSessionQuote()
    {
        return $this->getObject('Magento\Backend\Model\Session\Quote');
    }

    public function log($data, $force = false)
    {
        if ($force || $this->isTest() || $this->isDebug()) {
            $this->logger->info($data);
        }
    }

    public function critical($data, $force = false)
    {
        if ($force || $this->isTest() || $this->isDebug()) {
            $this->logger->critical($data);
        }
    }

    /**
     * Retrieve url
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route, $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }
}