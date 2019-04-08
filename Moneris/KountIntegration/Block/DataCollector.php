<?php

namespace Moneris\KountIntegration\Block;

use Magento\Framework\View\Element\Template;
use Moneris\KountIntegration\Helper\Data;

class DataCollector extends \Magento\Framework\View\Element\Template
{
    /** @var Data */
    private $helper;

    public function __construct(
        Template\Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    public function getImgUrl()
    {
        $sessionId = session_id();
        $merchantId = $this->helper->getMerchantID();
        $baseUrl = $this->helper->getDataCollectorBaseUrl();

        return $baseUrl . 'logo.gif?m=' . $merchantId . '&s=' . $sessionId;
    }

    public function getScriptUrl()
    {
        $sessionId = session_id();
        $merchantId = $this->helper->getMerchantID();
        $baseUrl = $this->helper->getDataCollectorBaseUrl();

        return $baseUrl . 'collect/sdk?m=' . $merchantId . '&s=' . $sessionId;
    }
}