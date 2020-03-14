<?php

namespace Moneris\MonerisCheckout\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;

class Data
{
    const PREREQUEST_ENDPOINT = 'https://gatewayt.moneris.com/chkt/request/request.php';
    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var ClientFactory */
    private $clientFactory;

    public function __construct(
        ScopeConfigInterface $storeConfig,
        ClientFactory $clientFactory
    )
    {
        $this->scopeConfig = $storeConfig;
        $this->clientFactory = $clientFactory;
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
        return self::PREREQUEST_ENDPOINT;
    }

    public function getStoreId()
    {
        return 'monca04342';
    }

    public function getApiToken()
    {
        return 'kkwS8tBGaPfY3OAaITKd';
    }

    public function getCheckoutId()
    {
        return 'chkt5DY2N04342';
    }

    public function getMode()
    {
        return 'qa';
    }
}
