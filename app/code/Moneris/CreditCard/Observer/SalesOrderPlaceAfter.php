<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Observer;

use Moneris\CreditCard\Helper\Data;
use Moneris\CreditCard\Model\RecurringPayment;
use Moneris\CreditCard\Model\ResourceModel\RecurringPayment as ResourceModel;
use Moneris\CreditCard\Model\RecurringPaymentFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManager\FactoryInterface;
use Magento\Sales\Model\Order;

class SalesOrderPlaceAfter implements ObserverInterface
{
    /**
     * @var ResourceModel
     */
    protected $resourceModel;

    /**
     * @var FactoryInterface
     */
    protected $recurringPaymentFactory;

    /**
     * @var Data
     */
    protected $data;

    /**
     * SalesOrderPlaceAfter constructor.
     * @param RecurringPaymentFactory $recurringPaymentFactory
     * @param ResourceModel $resourceModel
     * @param Data $data
     */
    public function __construct(
        RecurringPaymentFactory $recurringPaymentFactory,
        ResourceModel $resourceModel,
        Data $data
    ) {
        $this->recurringPaymentFactory = $recurringPaymentFactory;
        $this->resourceModel = $resourceModel;
        $this->data = $data;
    }

    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getData('order');
        $additionalInfo = $order->getPayment()->getAdditionalInformation();

        if (!empty($additionalInfo)) {
            if (!empty(@$additionalInfo['recurring']) && !empty(@$additionalInfo['data_key'])) {
                /** @var RecurringPayment $recurringPayment */
                $recurringPayment = $this->recurringPaymentFactory->create();
                $recurringPayment->setData('created_date', gmdate("Y-m-d\TH:i:s\Z"));
                $recurringPayment->setData('last_payment_date', gmdate("Y-m-d\TH:i:s\Z"));
                $nextPaymentDate = $this->data->convertTermToTime(@$additionalInfo['recurringTerm']);

                if ($this->data->isCCTestMode()) {
                    $recurringPayment->setData('next_payment_date', gmdate("Y-m-d\TH:i:s\Z"));
                } else {
                    $recurringPayment->setData('next_payment_date', gmdate("Y-m-d\TH:i:s\Z", time() + $nextPaymentDate));
                }
                $recurringPayment->setData('recurring_term', @$additionalInfo['recurringTerm']);
                $recurringPayment->setData('customer_id', $order->getCustomerId());
                $recurringPayment->setData('order_id', $order->getIncrementId());
                $recurringPayment->setData('amount', $order->getBaseGrandTotal());
                $recurringPayment->setData('data_key', $additionalInfo['data_key']);
                $this->resourceModel->save($recurringPayment);
            }
        }
    }
}