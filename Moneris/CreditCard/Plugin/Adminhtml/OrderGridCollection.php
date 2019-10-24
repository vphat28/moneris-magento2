<?php

namespace Moneris\CreditCard\Plugin\Adminhtml;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;

class OrderGridCollection
{
    public function __construct(
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->orderRepository = $orderRepository;
    }

    public function afterGetItems($subject, $result)
    {
        foreach ($result as $item) {
            /** @var Document $item */
            /** @var OrderInterface $order */
            $order = ObjectManager::getInstance()->create(OrderInterface::class);
            $order->loadByIncrementId($item->getData('increment_id'));
            $payment = $order->getPayment();
            $info = $payment->getAdditionalInformation();

            if (isset($info['card_type'])) {
                $item->setData('payment_method', 'chmoneriscc_' . strtolower($info['card_type']));
            }
        }

        return $result;
    }
}