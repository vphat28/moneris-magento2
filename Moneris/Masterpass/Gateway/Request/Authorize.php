<?php

namespace Moneris\Masterpass\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class Authorize implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var ConfigInterface
     */
    private $logger;
    
    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $session;
    
    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;
    
    /**
     *
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $sessionManager;
    
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        \Moneris\Masterpass\Gateway\Config\Config $config,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $session,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->session = $session;
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;
    }
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $this->logger->info("auth request start");
        return [];
    }
}
