<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\Masterpass\Controller\Index;

use Moneris\Masterpass\Model\Ui\ConfigProvider;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Payment extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Model\QuoteManagement $quoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        JsonFactory $resultJsonFactory,
        \Psr\Log\LoggerInterface $logger,
        \Moneris\Masterpass\Service\Transaction $transaction
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->transaction = $transaction;
    }

    public function execute()
    {
        $this->logger->info("start payment");
        $guestEmail = $this->_request->getParam('guestEmail');
        $this->logger->info("guest email = ".$guestEmail);
        $result = $this->resultJsonFactory->create();
        $quote = $this->checkoutSession->getQuote();
        if (!empty($guestEmail)) {
            $this->checkoutSession->setGuestEmail($guestEmail);
        }
        $response = $this->transaction->mpgRequest([
            'type'=>'paypass_send_shopping_cart',
            'subtotal'=> $quote->getGrandTotal(),
            'suppress_shipping_address'=>'true',
            'merchant_callback_url' => $this->_url->getUrl('chmasterpass/index/receipt')
        ]);
        $this->logger->info("s email = ".$quote->getShippingAddress()->getEmail());
        $this->logger->info("payment response = ".print_r($response, 1));
        $this->logger->info("url = ".$response->getMPRedirectUrl());
        if (!empty($response) && $response->getResponseCode() == '001') {
            $result->setData(['redirect_url' => urldecode($response->getMPRedirectUrl())]);
            $this->checkoutSession->setMpToken($response->getMPRequestToken());
        }
        return $result;
    }
}
