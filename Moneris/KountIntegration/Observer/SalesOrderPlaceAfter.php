<?php

namespace Moneris\KountIntegration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Moneris\KountIntegration\DataProvider\KountDataProvider;
use Moneris\KountIntegration\Helper\Data;
use Moneris\KountIntegration\Model\Logger;
use Kount_Ris_Request_Inquiry;
use Kount_Util_Khash;
use Kount_Ris_Data_CartItem;

class SalesOrderPlaceAfter implements ObserverInterface
{
    const AVSZ = 'M';
    const AVST = 'M';
    const CVVR = 'M';
    /** @var Data */
    private $data;

    /** @var KountDataProvider */
    private $dataProvider;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Logger */
    private $logger;

    public function __construct(
        Data $data,
        KountDataProvider $dataProvider,
        StoreManagerInterface $storeManager,
        Logger $logger
    )
    {
        $this->data = $data;
        $this->dataProvider = $dataProvider;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getData('order');
        $this->makeAPICall($order);
    }

    /**
     * @param Order $order
     * @throws \Kount_Ris_Exception
     * @throws \Kount_Ris_IllegalArgumentException
     * @throws \Kount_Ris_ValidationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function makeAPICall($order)
    {
        $url = $this->data->getUrl();
        $billingAddress = $order->getBillingAddress();
        $store = $order->getStoreId();
        $mStore = $this->storeManager->getStore($store);

        $kount_merchant_id = $this->data->getMerchantID($store);
        $kount_api_key=$this->data->getAPIKey($store);
        $order_id=$order->getIncrementId();
        $currency= $order->getOrderCurrency()->getCurrencyCode();
        $email = $billingAddress->getEmail();
        $session = session_id();
        $websiteId = $mStore->getCode();
        $names = [];
        $total = $order->getGrandTotal();

        foreach ([$billingAddress->getFirstname(), $billingAddress->getMiddlename(), $billingAddress->getLastname()] as $n) {
            if (!empty($n)) {
                $names[] = $n;
            }
        }

        if (!empty($this->dataProvider->getAdditionalData('cc_number'))) {
            $paymentType = 'CARD';
        } else {
            $paymentType = 'NONE';
        }

        switch ($paymentType) {
            case 'CARD':
                $payment_token = $this->dataProvider->getAdditionalData('cc_number');
                break;
            default;
                $payment_token = NULL;
                break;
        }

        $request = new Kount_Ris_Request_Inquiry(new \Kount_Ris_ArraySettings(
            [
                'MERCHANT_ID' => $kount_merchant_id,
                'URL' => $url,
                'CONNECT_TIMEOUT' => 300,
                'API_KEY' => $kount_api_key,
                'CONFIG_KEY' => '',
                'PEM_PASS_PHRASE' => '',
                'PEM_KEY_FILE' => '',
                'PEM_CERTIFICATE' => '',
            ]
        ));
        $request->setName(implode(' ', $names));
        $request->setEmail($email);
        $request->setSessionId($session);
        $request->setMerchantId($kount_merchant_id);
        $request->setUrl($url);
        $request->setApiKey($kount_api_key);
        $request->setMack('Y');
        $request->setMode('Q');
        $request->setPaymentMasked($payment_token);
        $request->setTotal($total);
        $request->setWebsite($websiteId);
        $request->setIpAddress($_SERVER['REMOTE_ADDR']);
        $request->setGender("M");
        $request->setAuth('A');
        $request->setAvst(self::AVST);
        $request->setAvsz(self::AVSZ);
        $request->setCvvr(self::CVVR);
       /* $request->setShippingAddress(S2A1, S2A2, S2CI, S2ST, S2PC, S2CC);
        $request->setBillingAddress($address1, $address2, $city, $state, $postalCode, 'US');*/
        //$request->setCash(CASH);

        $itemsArr = [];
        foreach ($order->getItems() as $item) {
            $cartItem = new Kount_Ris_Data_CartItem($item->getProductType(), $item->getName(), $item->getDescription(), $item->getQtyOrdered(), $item->getPrice()); //add 1 item for $9.95
            $itemsArr[] = $cartItem;
        }

        $request->setCart($itemsArr);
        $response = $request->getResponse();
        $status = $response->getErrorCode();
        $score = $response->getScore();

        if (!empty($score)) {
            $order->getPayment()->setAdditionalInformation('kount_score', $score);
            $order->save();
        }
        $this->logger->debug('kount response', [$response, $status, $score]);
    }
}