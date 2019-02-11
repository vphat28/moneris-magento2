<?php

namespace Moneris\Masterpass\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Moneris\Masterpass\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const MCODE = 'chmasterpass';

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Config $config
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        ResolverInterface $localeResolver,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->logger = $logger;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'chmasterpass' => [
                    'active' => $this->config->isActive(),
                    'title' => $this->config->getTitle(),
                ],
            ]
        ];
    }
}
