<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Index;

use Magento\Sales\Model\Order as OrderModel;
use Magento\Framework\Session\SessionManagerInterface;

class Redirect extends AbstractController
{
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    /**
     * AbstractController constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param SessionManagerInterface $checkoutSession
     * @param SessionManagerInterface $customerSession
     * @param \Moneris\CreditCard\Helper\Data $helperData
     * @param \Moneris\CreditCard\Model\Method\Payment $paymentMethod
     * @param OrderModel $order
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        SessionManagerInterface $checkoutSession,
        SessionManagerInterface $customerSession,
        \Moneris\CreditCard\Helper\Data $helperData,
        \Moneris\CreditCard\Model\Method\Payment $paymentMethod,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
        parent::__construct(
            $context,
            $checkoutSession,
            $customerSession,
            $helperData,
            $paymentMethod,
            $order,
            $coreRegistry
        );
    }
    
    public function execute()
    {
        $this->logger->info("execute redirect");
        $orderId = $this->_getCheckoutSession()->getMonerisccOrderId();
        
        if (!$orderId) {
            $this->_abort();
        }

        $order = $this->getOrderByIncrementId($orderId);
        $content =  $this->_getCheckoutSession()->getMonerisccMpiForm();

        if (!$content && !$this->_getCheckoutSession()->getMonerisccCancelOrder()) {
            if ($this->getHelper()->getIsVbvRequired()) {
                $message = __(
                    'Only VBV / 3DS enrolled cards are accepted. Please try another card or a different payment method.'
                );
                $this->messageManager->addError($message);
                if ($this->_getCheckoutSession()->getMonerisccCancelMessage()) {
                    $message = $this->_getCheckoutSession()->getMonerisccCancelMessage();
                }
                
                $this->_addOrderMessage($order, $message, OrderModel::STATE_CANCELED);
                $returnUrl = $this->getHelper()->getUrl(self::CART_PATH);
                $this->getResponse()->setRedirect($returnUrl);
            } else {
                $returnUrl = $this->getHelper()->getUrl(self::CHECKOUT_SUCCESS_PATH);
                $this->getResponse()->setRedirect($returnUrl);
            }
        } else {
            $this->getResponse()->setBody($content);
            $this->_addOrderMessage($order, __('Pending VBV / 3DS callback'), OrderModel::STATE_PAYMENT_REVIEW);
        }
    }
}
