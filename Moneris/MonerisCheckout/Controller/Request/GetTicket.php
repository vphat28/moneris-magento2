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

    /** @var \Moneris\CreditCard\Logger\Logger */
    private $logger;

    /** @var \Magento\Framework\View\Asset\Repository */
    protected $assetRepo;

    public function __construct(
        Context $context,
        UserContextInterface $userContext,
        ClientFactory $clientFactory,
        Data $data,
        Session $checkoutSession,
        QuoteRepository $quoteRepository,
        ProductRepositoryInterface $productRepository,
        \Moneris\CreditCard\Logger\Logger $logger,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        Image $imageHelper
    )
    {
        $this->userContext = $userContext;
        $this->clientFactory = $clientFactory;
        $this->data = $data;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->assetRepo = $assetRepo;
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

        $shipping = $quote->getShippingAddress();
        $shippingCost = $shipping->getShippingAmount();
        $billing = $quote->getBillingAddress();
        $shippingIcon = $this->assetRepo->getUrlWithParams('Moneris_MonerisCheckout::images/shipping-icon.png', ['_secure' => true]);
        /** @var Quote $quote */

        $requestData = new \stdClass;
        $requestData->store_id = $this->data->getStoreId();
        $requestData->api_token = $this->data->getApiToken();
        $requestData->checkout_id = $this->data->getCheckoutId();
        $requestData->integrator = "cr_dev";
        $requestData->txn_total = $this->formatPrice($quote->getGrandTotal());
        $requestData->environment = $this->data->getMode();
        $requestData->action = "preload";
        $requestData->order_no = $quoteId . '_' . time();
        $requestData->cust_id = $quote->getBillingAddress()->getEmail();
        $requestData->dynamic_descripto = "dyndesc";
        $requestData->cart = new \stdClass;
        $requestData->cart->items = [];

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

            if (!empty(floatval($shippingCost))) {
                $itemDataToSend = new \stdClass();
                $itemDataToSend->description = (string)__('Shipping cost');
                $itemDataToSend->unit_cost = $this->formatPrice($shippingCost);
                $itemDataToSend->quantity = 1;
                $itemDataToSend->url = $shippingIcon;

                $requestData->cart->items[] = $itemDataToSend;
            }
        }

        {
            $requestData->shipping_details              = new \stdClass();
            $requestData->shipping_details->address_1   =  is_array($shipping->getStreet()) ? implode(' ', $shipping->getStreet()) : (string)$shipping->getStreet();
//            $requestData->shipping_details->address_2   = $customer->get_shipping_address_2();
            $requestData->shipping_details->city        = $shipping->getCity();
            $requestData->shipping_details->province    = $shipping->getRegion();
            $requestData->shipping_details->country     = $shipping->getCountry();
            $requestData->shipping_details->postal_code = $shipping->getPostcode();
        }

        {
            $requestData->billing_details              = new \stdClass();
            $requestData->billing_details->address_1   = is_array($billing->getStreet()) ? implode(' ', $billing->getStreet()) : (string)$billing->getStreet();
            //$requestData->billing_details->address_2   = $billing->get();
            $requestData->billing_details->city        = $billing->getCity();
            $requestData->billing_details->province    = $billing->getRegion();
            $requestData->billing_details->country     = $billing->getCountry();
            $requestData->billing_details->postal_code = $billing->getPostcode();
        }

        $postedData = @json_decode(file_get_contents('php://input'), true);
        $inputedEmail = filter_var($postedData['email'], FILTER_SANITIZE_EMAIL);

        $requestData->contact_details = new \stdClass();
        $requestData->contact_details->first_name = $billing->getFirstname();
        $requestData->contact_details->last_name = $billing->getLastname();;
        $requestData->contact_details->email = empty($billing->getEmail()) ? $inputedEmail : $billing->getEmail();
        $requestData->contact_details->phone = $billing->getTelephone();

        $total = (float)$quote->getGrandTotal();
        $tax = $quote->getTotals()['tax'];
        $tax = $tax->getValue();
        $total_without_tax = $total - $tax;

        $requestData->cart->quote_id = $quoteId;
        $requestData->cart->subtotal = $total_without_tax;

        if ($tax > 0) {
            $tax_rate                            = bcdiv(
                bcmul( $tax, 100, 2 ),
                $total_without_tax );
            $tax_desc                            = 'Taxes';
            $requestData->cart->tax              = new \stdClass();
            $requestData->cart->tax->amount      = $tax;
            $requestData->cart->tax->description = $tax_desc;
            $requestData->cart->tax->rate        = $tax_rate;
        }

        $requestData->subtotal = $this->formatPrice($quote->getGrandTotal());

        $this->logger->debug('getting ticket..' . json_encode($requestData));

        $response = $client->post($url,
            ['body' => json_encode(
                $requestData
            )]
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->logger->debug(json_encode($body));

        if ($body['response']['success'] === "true" &&
            isset($body['response']['ticket']) &&
            !empty($body['response']['ticket'])
        ) {
            $result->setData([
                'ticket' => $body['response']['ticket'],
                'quote_id' => $quoteMask,
                'user' => $userId,
            ]);
        } else {
            $result->setData([
                'success' => false,
                'body' => $body
            ]);
        }

        return $result;
    }
}
