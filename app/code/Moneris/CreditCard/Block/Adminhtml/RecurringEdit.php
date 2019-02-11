<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Block\Adminhtml;

use Moneris\CreditCard\Model\RecurringPayment;
use Moneris\CreditCard\Model\ResourceModel\RecurringPayment as ResourceModel;
use Moneris\CreditCard\Model\RecurringPaymentFactory;
use Magento\Backend\Block\Template;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Spi\OrderResourceInterface;

class RecurringEdit extends Template
{
    /** @var RecurringPayment */
    protected $recurringProfile;

    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * RecurringEdit constructor.
     * @param Template\Context $context
     * @param RecurringPaymentFactory $recurringPaymentFactory
     * @param ResourceModel $resourceModel
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        RecurringPaymentFactory $recurringPaymentFactory,
        ResourceModel $resourceModel,
        OrderResourceInterface $orderRepository,
        OrderInterfaceFactory $orderFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $params = $this->_request->getParams();

        /** @var RecurringPayment $recurringProfile */
        $recurringProfile = $recurringPaymentFactory->create();
        $resourceModel->load($recurringProfile, $params['id'], 'entity_id');
        $this->recurringProfile = $recurringProfile;
        $this->order = $orderFactory->create();
        $orderRepository->load($this->order, $recurringProfile->getData('order_id'), 'increment_id');
    }

    /**
     * @return RecurringPayment
     */
    public function getRecurringProfile()
    {
        return $this->recurringProfile;
    }

    /**
     * @return OrderInterface
     */
    public function getOrder()
    {
        return $this->order;
    }
}
