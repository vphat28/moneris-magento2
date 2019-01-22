<?php

namespace Moneris\CreditCard\Controller\Mycards;

use Moneris\CreditCard\Controller\AbstractMycards;
use Moneris\CreditCard\Cron\ProceedRecurringPayment;
use Moneris\CreditCard\Helper\Data as chHelper;
use Moneris\CreditCard\Helper\Data;
use Moneris\CreditCard\Model\VaultSaveService;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class TestProceedRecurring extends \Magento\Framework\App\Action\Action
{
    /**
     * TestProceedRecurring constructor.
     * @param Context $context
     * @param ProceedRecurringPayment $cron
     */
    public function __construct(Context $context, ProceedRecurringPayment $cron)
    {
        parent::__construct($context);
        $this->cron = $cron;
    }

    public function execute()
    {
        /** @var Data $helper */
        $helper = ObjectManager::getInstance()->get(Data::class);

        if ($helper->isCCTestMode()) {
            $this->cron->execute();
        } else {
            return false;
        }
        return;
    }
}
