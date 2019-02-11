<?php

namespace Moneris\CreditCard\Controller\Adminhtml\RecurringPayment;

use Moneris\CreditCard\Model\RecurringPayment;
use Moneris\CreditCard\Model\RecurringPaymentFactory;
use Moneris\CreditCard\Model\ResourceModel\RecurringPayment as ResourceModel;
use Magento\Backend\App\Action;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * Delete constructor.
     * @param Action\Context $context
     * @param ResourceModel $resourceModel
     * @param RecurringPaymentFactory $recurringPaymentFactory
     */
    public function __construct(
        Action\Context $context,
        ResourceModel $resourceModel,
        RecurringPaymentFactory $recurringPaymentFactory
    )
    {
        parent::__construct($context);
        $this->resourceModel = $resourceModel;
        $this->recurringPaymentFactory = $recurringPaymentFactory;
    }

    public function execute()
    {
        $params = $this->_request->getParams();
        $result = $this->resultRedirectFactory->create();
        $result->setPath('moneris/recurringpayment/index');
        $this->messageManager->addSuccessMessage(__('Recurring payment has been deleted successfully'));
        $profile = $this->recurringPaymentFactory->create();
        $profile->setData('entity_id', $params['id']);
        $this->resourceModel->delete($profile);

        return $result;
    }


}