<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Block\Frontend;

use Magento\Customer\Model\Context;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Sales\Model\Order;

class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var SessionManagerInterface
     */
    private $checkoutSession;
    
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    private $orderConfig;
    
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;
    
    /**
     * @var \Moneris\CreditCard\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param SessionManagerInterface $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Moneris\CreditCard\Helper\Data $checkoutHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Moneris\CreditCard\Helper\Data $checkoutHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $context->getSession();
        $this->orderConfig = $orderConfig;
        $this->checkoutHelper = $checkoutHelper;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
    }
    
    protected function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }
    
    /**
     * Prepares block data
     *
     * @return void
     */
    public function prepareBlockData()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();
        
        $debitName = $this->getPaymentAdditionalInfo($payment, 'ISSNAME');
        $checkoutError = $this->checkoutSession->getResponseError();
        if (isset($checkoutError) &&
            isset($checkoutError['IDEBIT_ISSCONF']) &&
            is_array($checkoutError) &&
            $checkoutError['IDEBIT_ISSCONF']
        ) {
            $debitName = $checkoutError['IDEBIT_ISSNAME'];
        } elseif (isset($checkoutError) && is_array($checkoutError) && $checkoutError['ISSCONF']) {
             $debitName = $checkoutError['ISSNAME'];
        }
        $this->addData(
            [
                'is_order_visible' => $this->isVisible($order),
                'view_order_url' => $this->getUrl(
                    'sales/order/view/',
                    ['order_id' => $order->getEntityId()]
                ),
                'print_url' => $this->getUrl(
                    'sales/order/print',
                    ['order_id' => $order->getEntityId()]
                ),
                'can_print_order' => $this->isVisible($order),
                'can_view_order'  => $this->canViewOrder($order),
                'name_debit' => $this->getPaymentAdditionalInfo($payment, 'ISSNAME'),
                'number_debit' => $this->getPaymentAdditionalInfo($payment, 'ISSCONF'),
                'invoice_debit' => $this->getPaymentAdditionalInfo($payment, 'bank_transaction_id'),
                'amount'  => $this->checkoutHelper->formatPrice($order->getGrandTotal(), 2),
                'currency_code'  => $order->getOrderCurrencyCode(),
                'order_id'  => $order->getIncrementId()
            ]
        );
    }
    
    public function getPaymentAdditionalInfo(\Magento\Framework\DataObject $payment, $key = null)
    {
        $info = $payment->getAdditionalInformation();
        if (!is_array($info)) {
            return null;
        }
    
        if (!$key) {
            return $info;
        }
    
        if (!isset($info[$key])) {
            return null;
        }
    
        return $info[$key];
    }
    
    /**
     * Is order visible
     *
     * @param Order $order
     * @return bool
     */
    public function isVisible(Order $order)
    {
        return !in_array(
            $order->getStatus(),
            $this->orderConfig->getInvisibleOnFrontStatuses()
        );
    }
    
    /**
     * Can view order
     *
     * @param Order $order
     * @return bool
     */
    public function canViewOrder(Order $order)
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH)
        && $this->isVisible($order);
    }
}
