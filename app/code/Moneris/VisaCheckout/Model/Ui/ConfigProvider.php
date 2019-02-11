<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Moneris\VisaCheckout\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Class ConfigProvider
 * @codeCoverageIgnore
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'chvisa';

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Config
     */
    private $storeManager;

    /**
     * Constructor
     *
     * @param Config $config
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        ResolverInterface $localeResolver,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $isVisaCheckoutActive = $this->config->isActive();
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $isVisaCheckoutActive,
                    'title' => $this->config->getTitle(),
                    'api_key' => $this->config->getApiKey(),
                    'isDeveloperMode' => $this->config->isDeveloperMode(),
                    'success_url' => $this->storeManager->getStore()->getBaseUrl().'/chvisa/index/placeorder'
                ]
            ]
        ];
    }
}
