<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Api;

use Magento\Framework\Session\SessionManagerInterface;

class Declined extends \Moneris\CreditCard\Controller\Hosted
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Declined constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param SessionManagerInterface $customerSession
     * @param SessionManagerInterface $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Moneris\CreditCard\Model\Method\Hosted $paymentMethod
     * @param \Moneris\CreditCard\Helper\Data $checkoutHelper
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Sales\Model\Order $orderModel
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        SessionManagerInterface $customerSession,
        SessionManagerInterface $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Moneris\CreditCard\Model\Method\Hosted $paymentMethod,
        \Moneris\CreditCard\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Sales\Model\Order\Status $status
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $quoteRepository,
            $quoteManagement,
            $orderFactory,
            $logger,
            $paymentMethod,
            $checkoutHelper,
            $cart,
            $resultJsonFactory,
            $orderModel,
            $status
        );

    }

    public function execute()
    {
        $data = $this->getRequest()->getParams();
        if (!empty($data['trans_name']) && strpos($data['trans_name'], 'idebit_purchase') !== false) {
            $data['error_message'] = 'Payment could not be processed at this time. Please try again later.';
            $this->checkoutSession->setResponseError($data);
            $this->_redirect('moneriscc/index/error');
            return;
        }

        if (isset($data)) {
            if (isset($data['cancelTXN'])) {
                $this->messageManager->addErrorMessage($data['cancelTXN']);
            } else {
                $message = $data['message'];
                $arrayError = $this->getCheckoutHelper()->hostedResponse;
                if (array_key_exists($data['response_code'], $arrayError) && !empty($data['response_code'])) {
                    $message = $arrayError[$data['response_code']];
                }

                $this->messageManager->addErrorMessage($message);
            }
        }

        $this->_redirect('checkout/cart');
    }
}
