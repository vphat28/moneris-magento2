<?php

namespace Moneris\Masterpass\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class Capture implements BuilderInterface
{

    
    /**
     * @var ConfigInterface
     */
    private $logger;
    
    /**
     * @var ConfigInterface
     */
    private $config;
    
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        \Moneris\Masterpass\Gateway\Config\Config $config,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $this->logger->info("capture request start");
        return [];
    }
}
