<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Index;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Sales\Model\Order as OrderModel;

abstract class AbstractController extends \Magento\Framework\App\Action\Action
{
    const PARAM_PARES = 'PaRes';
    const PARAM_MD = 'MD';
    const CART_PATH = 'checkout/cart';
    const CHECKOUT_PATH = 'checkout/onepage';
    const CHECKOUT_SUCCESS_PATH = 'checkout/onepage/success';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry = null;

    /**
     * @var SessionManagerInterface $checkoutSession
     */
    private $checkoutSession;

    /**
     * @var SessionManagerInterface $customerSession
     */
    private $customerSession;

    /**
     * @var \Moneris\CreditCard\Helper\Data $helperData
     */
    private $helperData;

    /**
     * @var \Moneris\CreditCard\Model\Method\Payment $paymentMethod
     */
    private $paymentMethod;

    /**
     * @var OrderModel $order
     */
    private $order;

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
        \Magento\Framework\Registry $coreRegistry
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->helperData = $helperData;
        $this->paymentMethod = $paymentMethod;
        $this->order = $order;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * @return SessionManagerInterface|\Magento\Checkout\Model\Session
     */
    public function _getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /**
     * @return SessionManagerInterface|\Magento\customer\Model\Session
     */
    public function _getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * Get session model
     *
     * @return \Moneris\CreditCard\Helper\Data
     */
    public function getHelper()
    {
        return $this->helperData;
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function getOrder($orderId)
    {
        return $this->order->load($orderId);
    }

    public function getOrderByIncrementId($incrementId)
    {
        return $this->order->loadByIncrementId($incrementId);
    }

    public function execute()
    {
        $this->_getOrbitalProcessor()->log(__METHOD__ . __LINE__);
        throw new LocalizedException(__(" must be overloaded "));
    }
    
    public function _abort($message = null)
    {
        $returnUrl = $this->getHelper()->getUrl(self::CART_PATH);
        if ($message) {
            $this->messageManager->addError($message);
        }
        
        $this->getResponse()->setRedirect($returnUrl);

        return $this;
    }

    // TODO move to helper? or model
    public function _addOrderMessage($order, $message, $status = null)
    {
        $originalStatus = $status;
        if (!$status) {
            $status = $order->getStatus();
        }
        
        if ($originalStatus != OrderModel::STATE_CANCELED) {
            $order->setState(
                $status,
                $status,
                __($message),
                false
            );
        }

        if ($originalStatus == OrderModel::STATE_CANCELED) {
            $this->_cancelPayment($order, $message);
            // TODO implement delete order
        } else {
            // TODO implement delete order
        }
        
        return $this;
    }
    /**
     * Cancel order, return quote to customer
     *
     * @param string $errorMsg
     * @return false|string
     */
    public function _cancelPayment($order, $errorMsg = '')
    {
        $gotoSection = false;
        $this->cancelCurrentOrder($order, $errorMsg);
        if ($this->_getCheckoutSession()->restoreQuote()) {
            //Redirect to payment step
            $gotoSection = 'paymentMethod';
        }
    
        return $gotoSection;
    }
    
    public function cancelCurrentOrder($order, $comment)
    {
        if ($order->getId() && $order->getState() != OrderModel::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        
        return false;
    }
}
