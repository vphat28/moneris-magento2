<?php

namespace Moneris\MonerisCheckout\Controller\Request;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Moneris\MonerisCheckout\Helper\Data;
use Magento\Catalog\Helper\Image;

class Getticket extends Action
{
    const PREREQUEST_ENDPOINT = 'https://gatewayt.moneris.com/chkt/request/request.php';

    /** @var UserContextInterface */
    private $userContext;

    /** @var ClientFactory */
    private $clientFactory;

    /** @var Data */
    private $data;

    /** @var Session $checkoutSession */
    private $checkoutSession;

    /** @var QuoteRepository */
    private $quoteRepository;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var Image */
    private $imageHelper;

    public function __construct(
        Context $context,
        UserContextInterface $userContext,
        ClientFactory $clientFactory,
        Data $data,
        Session $checkoutSession,
        QuoteRepository $quoteRepository,
        ProductRepositoryInterface $productRepository,
        Image $imageHelper
    )
    {
        $this->userContext = $userContext;
        $this->clientFactory = $clientFactory;
        $this->data = $data;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        parent::__construct($context);
    }

    private function getPlaceHolderImages()
    {
        return $this->imageHelper->getDefaultPlaceholderUrl();
    }

    /**
     * @param $quoteId
     * @return mixed
     */
    private function getQuoteMaskIdFromQuoteId($quoteId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $tableName = $resource->getTableName('quote_id_mask');
        $query = $connection->select()->from($tableName)->where('quote_id=?', $quoteId);
        $result = $connection->fetchAll($query);

        if (is_array($result) && !empty($result)) {
            return $result[0]['masked_id'];
        }

        return null;
    }

    private function formatPrice($number)
    {
        return number_format((float)$number, 2, '.', '');
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $url = self::PREREQUEST_ENDPOINT;
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        /** @var Client $client */
        $client = $this->clientFactory->create([
            'headers' => ['Content-Type' => 'application/json']
        ]);

        // Get quote

        $userId = $this->userContext->getUserId();

        // If guest
        if (empty($userId)) {
            $quoteId = $this->checkoutSession->getQuoteId();
            $quote = $this->quoteRepository->get($quoteId);
            $quoteMask = $this->getQuoteMaskIdFromQuoteId($quoteId);
        } else {
            $quoteMask = '';
            $quote = $this->quoteRepository->getActiveForCustomer($userId);
            $quoteId = $quote->getId();
        }

        /** @var Quote $quote */

        $requestData = new \stdClass;
        $requestData->store_id = $this->data->getStoreId();
        $requestData->api_token = $this->data->getApiToken();
        $requestData->checkout_id = $this->data->getCheckoutId();
        $requestData->integrator = "cr_dev";
        $requestData->txn_total = $this->formatPrice($quote->getGrandTotal());
        $requestData->environment = $this->data->getMode();
        $requestData->action = "preload";
        $requestData->order_no = md5($quoteId . $this->data->getApiToken() . $this->data->getCheckoutId());
        $requestData->cust_id = "chkt - cust";
        $requestData->dynamic_descripto = "dyndesc";
        $requestData->cart = new \stdClass;
        $requestData->cart->items = [];
        $requestData->shipping_rates = [];

        $newRate = new \stdClass();

        $newRate->code = "code01";
        $newRate->description = "Standard";
        $newRate->date = "3 days";
        $newRate->amount = "$10";
        $newRate->txn_taxes = "1.00";
        $newRate->txn_total = "10.00";
        $newRate->default_rate = "false";
        $requestData->shipping_rates[] = $newRate;

        $quoteItems = $quote->getItems();
        $placeHolderImage = $this->getPlaceHolderImages();

        if (!empty($quoteItems)) {
            foreach ($quoteItems as $item) {
                $product = $this->productRepository->get($item->getSku());
                $itemDataToSend = new \stdClass();

                if (empty($product->getData('thumbnail'))) {
                    $itemDataToSend->url = $placeHolderImage;
                } else {
                    $this->imageHelper->setImageFile($product->getData('thumbnail'));
                    $itemDataToSend->url = $this->imageHelper->getUrl();
                }


                $itemDataToSend->description = $product->getName();
                $itemDataToSend->product_code = $product->getSku();
                $itemDataToSend->unit_cost = $this->formatPrice($item->getPrice());
                $itemDataToSend->quantity = $item->getQty();

                $requestData->cart->items[] = $itemDataToSend;
            }
        }

        $requestData->cart->quote_id = $quoteId;

        $requestData->subtotal = $this->formatPrice($quote->getGrandTotal());

        $response = $client->post($url,
            ['body' => json_encode(
                $requestData
            )]
        );

        $body = json_decode($response->getBody()->getContents(), true);

        if ($body['response']['success'] === "true") {
            $result->setData([
                'ticket' => $body['response']['ticket'],
                'quote_id' => $quoteMask,
                'user' => $userId,
            ]);
        } else {
            var_dump($body);
            $result->setData([
                'success' => false,
            ]);
        }

        return $result;
    }
}
