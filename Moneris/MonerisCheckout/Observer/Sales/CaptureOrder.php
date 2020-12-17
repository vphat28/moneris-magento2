<?php

namespace Moneris\MonerisCheckout\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Moneris\MonerisCheckout\Helper\Data;

class CaptureOrder implements ObserverInterface
{
    /** @var \Magento\Sales\Api\InvoiceManagementInterface */
    protected $invoiceManagement;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var Data */
    protected $data;

    /** @var \Magento\Sales\Model\Order\Status */
    protected $statusFactory;

    public function __construct(
        \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement,
        OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\StatusFactory $statusFactory,
        \Magento\Sales\Model\ResourceModel\Status\CollectionFactory $statusCollectionFactory,
        Data $data
    )
    {
        $this->invoiceManagement = $invoiceManagement;
        $this->orderRepository = $orderRepository;
        $this->statusFactory = $statusFactory;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->data = $data;
    }

    public function execute( Observer $observer ) {
	    /** @var \Magento\Sales\Model\Order $order */
		$order = $observer->getData('order');
		$payment = $order->getPayment();
		$info = $payment->getAdditionalInformation();

		if (isset($info['moneris_checkout_payment_action']) && $info['moneris_checkout_payment_action'] === '00') {
		    // make an invoice
            $this->makeInvoice($order);
        }
	}

	protected function makeInvoice($order)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $payment = $order->getPayment();
        $amount  = $payment->getBaseAmountAuthorized();
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $this->invoiceManagement->prepareInvoice($order);
        $invoice->pay();
        $invoice->register();
        $invoice->setCanVoidFlag(false);
        $invoice->setTransactionId($payment->getCcTransId());

        $message = 'Captured amount of %1.';
        $message = __($message, $amount);

        $orderStatus = empty($this->data->getOrderStatus()) ? Order::STATE_PROCESSING : $this->data->getOrderStatus();
        $state = Order::STATE_PROCESSING;
        /** @var \Magento\Sales\Model\ResourceModel\Status\Collection $statuses */
        $statuses = $this->statusCollectionFactory->create();
        $allStatuses = $statuses->getItems();

        foreach ($allStatuses as $st) {
            if ($st->getData('status') == $orderStatus) {
                $state = $st->getData('state');
            }
        }

        $order->setState($state);
        $order->setStatus($orderStatus);
        $transaction = $payment->getCcTransId();
        $payment->addTransactionCommentsToOrder($transaction, $message);
        $payment->setIsTransactionClosed(false);
        $order->setTotalPaid($amount);
        $order->addRelatedObject($invoice);
        $this->orderRepository->save($order);
    }

}
