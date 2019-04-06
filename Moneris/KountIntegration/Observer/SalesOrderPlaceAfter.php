<?php

namespace Moneris\KountIntegration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Moneris\KountIntegration\DataProvider\KountDataProvider;
use Moneris\KountIntegration\Helper\Data;
use Moneris\KountIntegration\Model\Logger;

class SalesOrderPlaceAfter implements ObserverInterface
{
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
        $this->makeCall($order);
    }

    /**
     * @param $order
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function makeCall($order)
    {
        $billingAddress = $order->getBillingAddress();
        $store = $order->getStoreId();
        $mStore = $this->storeManager->getStore($store);
        $store_id = $this->data->getMonerisStoreID($store);
        $api_token = $this->data->getMonerisAPIToken($store);;
        /********************* Transactional Variables ************************/
        $type = 'kount_inquiry';
        $kount_merchant_id = $this->data->getMerchantID($store);
        $kount_api_key=$this->data->getAPIKey($store);
        $order_id=$order->getIncrementId();
        $currency= $order->getOrderCurrency()->getCurrencyCode();
        $email = $billingAddress->getEmail();
        $session = session_id();
        $websiteId = $mStore->getCode();

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

        $txnArray = [
            'order_id'=>$order_id,
            'kount_merchant_id'=>$kount_merchant_id,
            'kount_api_key'=>$kount_api_key,
            'type'=>$type,
            'payment_response'=> 'A',
            'payment_token' => $payment_token,
            'payment_type' => $paymentType,
            'currency' => $currency,
            'call_center_ind' => 'N',
            'session_id' => $session,
            'website_id' => $websiteId,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'amount' => $order->getGrandTotal(),
            'email' => $email,
        ];

        $i = 0;
        foreach ($order->getItems() as $item) {
            $i++;
            $txnArray['prod_type_' . $i] = $item->getProductType();
            $txnArray['prod_item_' . $i] = $item->getSku();
            $txnArray['prod_desc_' . $i] = $item->getDescription();
            $txnArray['prod_price_' . $i] = $item->getPriceInclTax();
            $txnArray['prod_quant_1' . $i] = $item->getQtyOrdered();
        }


        $kountTxn = new \kountTransaction($txnArray);

        /************************ Request Object ******************************/

        $kountRequest = new \kountRequest($kountTxn);

        /*********************** HTTPS Post Object ****************************/

        $kountHttpsPost  =new \kountHttpsPost($store_id,$api_token,$kountRequest, $this->data->getTestMode($store));

        /***************************** Response ******************************/

        $kountResponse=$kountHttpsPost->getkountResponse();

        print("\nResponseCode = " . $kountResponse->getResponseCode());
        print("\nReceiptId = " . $kountResponse->getReceiptId());
        print("\nMessage = " . $kountResponse->getMessage());
        print("\nKountResult = " . $kountResponse->getKountResult());
        print("\nKountScore = " . $kountResponse->getKountScore());

        $kountInfo = $kountResponse->getKountInfo();

        $this->logger->debug('Kount Response', $kountInfo);
    }
}