<?php

namespace Moneris\CreditCard\Cron;

use Moneris\CreditCard\Helper\Data;
use Moneris\CreditCard\Model\RecurringPaymentQueue;
use Moneris\CreditCard\Model\ResourceModel\RecurringPaymentQueue as ResourceQueueModel;
use Moneris\CreditCard\Model\RecurringPaymentQueueFactory;
use Moneris\CreditCard\Model\RecurringPayment;
use Moneris\CreditCard\Model\ResourceModel\RecurringPayment as ResourceModel;
use Moneris\CreditCard\Model\ResourceModel\RecurringPayment\Collection;
use Moneris\CreditCard\Model\ResourceModel\RecurringPayment\CollectionFactory;
use Moneris\CreditCard\Model\Transaction;
use Moneris\CreditCard\Logger\Logger;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class ProceedRecurringPayment
{
    const CODE = 'chmoneriscc';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var ResourceModel
     */
    protected $resourceModel;

    /**
     * @var ResourceQueueModel
     */
    protected $resourceQueueModel;

    /**
     * @var RecurringPaymentQueueFactory
     */
    protected $recurringPaymentQueueFactory;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var OrderInterfaceFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderResource
     */
    protected $orderResourceModel;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $data;

    /**
     * ProceedRecurringPayment constructor.
     * @param CollectionFactory $collectionFactory
     * @param ResourceModel $resourceModel
     * @param DirectoryList $directoryList
     * @param ResourceQueueModel $resourceQueueModel
     * @param RecurringPaymentQueueFactory $recurringPaymentQueueFactory
     * @param Transaction $transaction
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderResource $orderResourceModel
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $logger
     * @param Data $data
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ResourceModel $resourceModel,
        DirectoryList $directoryList,
        ResourceQueueModel $resourceQueueModel,
        RecurringPaymentQueueFactory $recurringPaymentQueueFactory,
        Transaction $transaction,
        OrderInterfaceFactory $orderFactory,
        OrderResource $orderResourceModel,
        OrderRepositoryInterface $orderRepository,
        Logger $logger,
        Data $data,
        ObjectManagerInterface $objectManager
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->resourceModel = $resourceModel;
        $this->directoryList = $directoryList;
        $this->resourceQueueModel = $resourceQueueModel;
        $this->recurringPaymentQueueFactory = $recurringPaymentQueueFactory;
        $this->transaction = $transaction;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->orderResourceModel = $orderResourceModel;
        $this->logger = $logger;
        $this->data = $data;
        $this->objectManager = $objectManager;
    }

    public function execute()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $mediaDir = $this->directoryList->getPath('media');
        $collection->getSelect()->where('DATE(next_payment_date) = curdate()');

        $file = $mediaDir . '/moneris_recurring/' . time() . '.csv';

        if (!is_dir($mediaDir . '/moneris_recurring')) {
            mkdir($mediaDir . '/moneris_recurring');
        }

        $f = fopen($file, 'c');

        if ($collection->count() == 0) {
            return $this;
        }

        $login = $this->data->getConfigData('payment/chmoneriscc/sftp_login', true);
        $password = $this->data->getConfigData('payment/chmoneriscc/sftp_password', true);

        if ($this->data->isCCTestMode()) {
            $server = Data::MONERIS_TEST_HOST;
        } else {
            $server = Data::MONERIS_HOST;
        }

        $connection = ssh2_connect($server, 22);
        ssh2_auth_password($connection, $login, $password);

        $sftp = ssh2_sftp($connection);
        $sftpInt = (int)$sftp;

        foreach ($collection as $item) {
            /** @var RecurringPayment $item */
            $data = $item->getData();
            $monerisOrderId = 'recurring-' . md5(time() . '-' . $data['order_id']) . '-payment';
            $term = $data['recurring_term'];
            fputcsv($f, [
                'res_purchase_cc',
                $monerisOrderId,
                $data['customer_id'],
                number_format($data['amount'], 2, '.', ''),
                $data['data_key'],
                '2'
            ]);

            $item->setData('last_payment_date', gmdate("Y-m-d\TH:i:s\Z"));
            $item->setData('next_payment_date', gmdate("Y-m-d\TH:i:s\Z", time() + $this->data->convertTermToTime($term)));
            $this->resourceModel->save($item);

            /** @var RecurringPaymentQueue $queue */
            $queue = $this->recurringPaymentQueueFactory->create();
            $queue->setData('moneris_order_id', $monerisOrderId);
            $queue->setData('order_id', $data['order_id']);
            $queue->setData('data_key', $data['data_key']);
            $this->resourceQueueModel->save($queue);
        }

        //Uploading
        file_put_contents('ssh2.sftp://' . $sftpInt . '/' . time() . '.csv', file_get_contents($file));

        return $this;
    }

    /**
     * This cron task will get orders from the queue and check if it's created on
     * Moneris server side, if yes then we will create order in Magento
     */
    public function proceedQueue()
    {
        /** @var RecurringPaymentQueue $queue */
        $queue = $this->recurringPaymentQueueFactory->create();
        $collection = $queue->getCollection();
        /** @var Registry $register */
        $register = $this->objectManager->get(Registry::class);
        $register->register('by_pass_authorize_payment', true);

        foreach ($collection as $queueItem) {
            /** @var RecurringPaymentQueue $queueItem */
            $queueItemData = $queueItem->getData();

            $status = $this->transaction->checkOrderStatus([
                    'type' => 'purchase',
                    'order_id' => $queueItemData['moneris_order_id'],
            ], self::CODE);

            $data = $status->getData()['raw_data'];

            if ($data['status_code'] === '005') {
                $this->createMagentoOrder($queueItemData, $data['TransID'], $data['ReceiptId']);
                $this->resourceQueueModel->delete($queueItem);
            } else {
                $this->logger->error($queueItemData['moneris_order_id'] . " is still not proceeded in Moneris");
            }
        }
    }

    /**
     * Create Magento Order
     */
    private function createMagentoOrder($queueItemData, $transId, $receiptId)
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->create();
        $this->orderResourceModel->load($order, $queueItemData['order_id'], 'increment_id');

        /** @var QuoteRepository $quoteRepo */
        $quoteRepo = $this->objectManager->get(QuoteRepository::class);
        $quote = $quoteRepo->get($order->getQuoteId());
        $quote->getPayment()->setAdditionalInformation('data_key', $queueItemData['data_key']);
        $quote->getPayment()->setAdditionalInformation('moneris_trans_id', $transId);
        $quote->getPayment()->setAdditionalInformation('moneris_receipt_id', $receiptId);

        /** @var QuoteManagement $quoteManagement*/
        $quoteManagement = $this->objectManager->get(QuoteManagement::class);

        try {
            /** @var Order $newOrder */
            $newOrder = $quoteManagement->submit($quote);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
