<?php

namespace Moneris\MonerisCheckout\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class CaptureOrder implements ObserverInterface
{
    /** @var \Magento\Sales\Api\InvoiceManagementInterface */
    protected $invoiceManagement;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    public function __construct(
        \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement,
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->invoiceManagement = $invoiceManagement;
        $this->orderRepository = $orderRepository;
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

	    // TODO: Implement execute() method.
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
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus(Order::STATE_PROCESSING);
        $transaction = $payment->getCcTransId();
        $payment->addTransactionCommentsToOrder($transaction, $message);
        $payment->setIsTransactionClosed(false);
        $order->setTotalPaid($amount);
        $order->addRelatedObject($invoice);
        $this->orderRepository->save($order);
    }

}
