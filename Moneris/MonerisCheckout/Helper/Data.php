<?php

namespace Moneris\MonerisCheckout\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use Magento\Store\Model\ScopeInterface;

class Data implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    const PREREQUEST_ENDPOINT = 'https://gatewayt.moneris.com/chkt/request/request.php';

    const PREREQUEST_ENDPOINT_PROD = 'https://gateway.moneris.com/chkt/request/request.php';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var ClientFactory */
    private $clientFactory;

    /** @var \Moneris\CreditCard\Helper\Data */
    private $creditCardHelper;

    public function __construct(
        ScopeConfigInterface $storeConfig,
        \Moneris\CreditCard\Helper\Data $creditCardHelper,
        ClientFactory $clientFactory
    )
    {
        $this->scopeConfig = $storeConfig;
        $this->clientFactory = $clientFactory;
        $this->creditCardHelper = $creditCardHelper;
    }

    public function getReceiptData($ticket)
    {
        $url = $this->getEndpoint();

        /** @var Client $client */
        $client = $this->clientFactory->create([
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $requestData = new \stdClass;
        $requestData->store_id = $this->getStoreId();
        $requestData->api_token = $this->getApiToken();
        $requestData->checkout_id = $this->getCheckoutId();
        $requestData->ticket = $ticket;
        $requestData->environment = $this->getMode();
        $requestData->action = 'receipt';

        $response = $client->post($url,
            ['body' => json_encode(
                $requestData
            )]
        );

        $body = json_decode($response->getBody()->getContents(), true);

        return $body;
    }

    public function getEndpoint()
    {
        if ($this->getIsTestMode()) {
            return self::PREREQUEST_ENDPOINT;
        } else {
            return self::PREREQUEST_ENDPOINT_PROD;
        }
    }

    public function getIsTestMode()
    {
        return $this->creditCardHelper->isCCTestMode();
    }

    public function getStoreId()
    {
        return $this->creditCardHelper->getMonerisStoreId();
    }

    public function getApiToken()
    {
        return $this->creditCardHelper->getMonerisApiToken();
    }

    public function getCheckoutId()
    {
        return $this->scopeConfig->getValue('payment/moneris/chmoneriscc/moneris_checkout_id');
    }

    public function isActive()
    {
        return (bool)$this->scopeConfig->isSetFlag('payment/moneris/chmoneriscc/active', ScopeInterface::SCOPE_WEBSITE);
    }

    public function getOrderStatus()
    {
        return $this->scopeConfig->getValue('payment/moneris/chmoneriscc/order_status');
    }

    public function getMode()
    {
        return $this->creditCardHelper->isCCTestMode() ? 'qa' : 'live';
    }

    public function getCardTypes()
    {
        return [
            'V' => 'Visa',
            'M' => 'Mastercard',
            'AX' => 'American Express',
            'DC' => 'Diner\'s Card',
            'NO' => 'Novus/Discover',
            'SE' => 'Sears',
            'D' => 'INTERAC® Debit',
            'C1' => 'JCB',
        ];

    }
}
