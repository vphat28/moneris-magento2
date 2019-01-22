<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Interac;

use Magento\Framework\Session\SessionManagerInterface;

class Funded extends \Moneris\CreditCard\Controller\Interac
{
    
    /**
     * @var \Magento\Sales\Model\Order\Status
     */
    public $status;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        SessionManagerInterface $customerSession,
        SessionManagerInterface $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Moneris\CreditCard\Model\Method\Interac $paymentMethod,
        \Moneris\CreditCard\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Sales\Model\Order\Status $status 
    ) {
        $this->status = $status;
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
            $orderModel
        );
    }

    public function execute()
    {
        // Initialize return url
        $returnUrl = $this->getCheckoutHelper()->getUrl('moneriscc/index/error');
        // Get params from response
        $params = $this->getRequest()->getParams();
        $this->getCheckoutHelper()->log(__METHOD__ . __LINE__ ." response from interac: " . print_r($params, 1));
        $this->checkoutSession->setResponseError($params);
        $messageResponse = 'Payment could not be processed at this time. Please try again later.';
        $this->checkoutSession->setIsFunded(true);

        if (isset($params['IDEBIT_ISSNAME'])) {
            $params['OLD_IDEBIT_ISSNAME'] = $params['IDEBIT_ISSNAME'];
            $params['IDEBIT_ISSNAME'] = utf8_encode($params['IDEBIT_ISSNAME']);
        }

        //check for required fields
        if (!$this->testFundedRequiredFields($params)) {
            $this->getCheckoutHelper()->log(__METHOD__ . __LINE__ ." required field missing in funded controller ");
            $this->getResponse()->setRedirect($returnUrl);
            return;
        }

        //check for lengths on fields

        if (!$this->testFundedFieldLengths($params)) {
            $this->getCheckoutHelper()->log(__METHOD__ . __LINE__ ." at least one field length is too long ");
            $this->getResponse()->setRedirect($returnUrl);
            return;
        }

        //check for invalid characters

        if (!$this->testFundedValidChars($params)) {
            $this->getCheckoutHelper()->log(__METHOD__ . __LINE__ ." invalid characters found ");
            $this->getResponse()->setRedirect($returnUrl);
            return;
        }

        try {
            $paymentMethod = $this->getPaymentMethod();
            // Get payment method code
            $code = $paymentMethod->getCode();
            $customerSession = $this->getCustomerSession();
            if ($params && $params[self::IDEBIT_TRACK2] && $params[self::IDEBIT_INVOICE]) {
            // Get quote from session
                $quoteId = $this->getQuote()->getId();
                $quote = $this->quote->load($quoteId);
                $responseId = $params[self::IDEBIT_INVOICE];
                $responseId = explode("-", $responseId);

                if (!empty($responseId) && $responseId[0] == $quoteId) {
                    if (!$customerSession->isLoggedIn()) {
                        $quote->setCustomerIsGuest(1);
                        $quote->setCheckoutMethod('guest');
                        $quote->setCustomerId(null);
                        $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
                        $quote->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                        $customerId = 0;
                    } else {
                        $customerId = $customerSession->getId();
                    }
                
                    $quote->setPaymentMethod($code);
                    $quote->setInventoryProcessed(false);
                    $quote->save();
                    $quote->getPayment()->importData(
                        [
                            'method' => $code,
                            'last_trans_id' => $params[self::IDEBIT_TRACK2],
                            'cc_trans_id' => $params[self::IDEBIT_TRACK2]
                        ]
                    );
                    // Collect Totals & Save Quote
                    $quote->collectTotals()->save();
                    $quoteId = $quote->getId();
                    // Create Order From Quote
                    $order = $this->quoteManagement->submit($quote);
                    $payment = $order->getPayment();
                    $transId = $params[self::IDEBIT_TRACK2];

                    $payment->setAdditionalInformation('ISSNAME', $params['IDEBIT_ISSNAME']);
                    $payment->setAdditionalInformation('ISSCONF', $params['IDEBIT_ISSCONF']);
                    $payment->setAdditionalInformation('INVOICE', $params['IDEBIT_INVOICE']);
                
                    $payment->save();
                
                    $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
                    $this->checkoutSession->setLastQuoteId($quote->getId());
                    $this->checkoutSession->setIncrementId($order->getIncrementId());
                    $this->checkoutSession->setLastOrderStatus($order->getStatus());
                    $this->checkoutSession->setLastRealOrderId($order->getRealOrderId());
                
                    $params['real_order_id'] = $order->getRealOrderId();
                    $params['bank_transaction_id'] = $transId;
                    $params['response_order_id'] = $params[self::IDEBIT_INVOICE];
                    
                    $this->paymentMethod->process($params);
                    $this->getCheckoutHelper()->log(__METHOD__ . __LINE__ ." success ! ".print_r($params, 1));
                    
                    $this->_setOrderStatus($order);

                    $returnUrl = $this->getCheckoutHelper()->getUrl('moneriscc/index/success');
                } else {
                    $returnUrl = $this->getCheckoutHelper()->getUrl('moneriscc/index/error');
                }
            } else {
                $returnUrl = $this->getCheckoutHelper()->getUrl('moneriscc/index/error');
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e, __($messageResponse));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __($messageResponse));
        }
        $this->getResponse()->setRedirect($returnUrl);
    }

    private function testFundedRequiredFields($params)
    {
        $required_fields = [
            "IDEBIT_INVOICE",
            "IDEBIT_MERCHDATA",
            "IDEBIT_ISSLANG",
            "IDEBIT_TRACK2",
            "IDEBIT_ISSCONF",
            "IDEBIT_ISSNAME",
            "IDEBIT_VERSION"
        ];
        // check for required fields
        foreach ($required_fields as $field) {
            if ((isset($params[$field]) && $params[$field] == '') || !isset($params[$field])) {
                $this->getCheckoutHelper()->log(
                    __METHOD__ . __LINE__ .
                    " required field missing in funded controller ".print_r($field, 1)
                );
                return false;
            }
        }
        $quoteId = $this->getQuote()->getId();
        if ($params['IDEBIT_MERCHDATA'] != $quoteId) {
            $this->getCheckoutHelper()->log(
                __METHOD__ . __LINE__ ." merch data doesnt match what was sent : " . $params['IDEBIT_MERCHDATA']
            );
            return false;
        }

        if ($params['IDEBIT_INVOICE'] != $quoteId) {
            $this->getCheckoutHelper()->log(
                __METHOD__ . __LINE__ .
                " idebit invoice number doesnt match what was sent : " . $params['IDEBIT_MERCHDATA']
            );
            return false;
        }

        return true;
    }

    private function testFundedValidChars($params)
    {
        if (isset($params['IDEBIT_ISSCONF'])) {
            $oldIssConf = $params['IDEBIT_ISSCONF'];
            $params['IDEBIT_ISSCONF'] = utf8_encode($params['IDEBIT_ISSCONF']);
        }
        $chars = [
            195,
            128,
            195,
            129,
            195,
            130,
            195,
            132,
            195,
            136,
            195,
            137,
            195,
            138,
            195,
            139,
            195,
            142,
            195,
            143,
            195,
            148,
            195,
            153,
            195,
            155,
            195,
            156,
            195,
            135,
            195,
            160,
            195,
            161,
            195,
            162,
            195,
            164,
            195,
            168,
            195,
            169,
            195,
            170,
            195,
            171,
            195,
            174,
            195,
            175,
            195,
            180,
            195,
            185,
            195,
            187,
            195,
            188,
            195,
            191,
            195,
            167
        ];
        $regexlist = '�������������������������������\s0-9a-z\#\$\.\,\-\/=\?\@\'';

        foreach ($chars as $t) {
            $regexlist .= chr($t);
        }

        $regexp = '/^['.$regexlist.']+$/i';
        if (!preg_match($regexp, $params['IDEBIT_ISSCONF'], $conftrash) ||
            !preg_match($regexp, $params['IDEBIT_ISSNAME'], $conftrash) ||
            ($params['IDEBIT_ISSLANG'] != 'en' && $params['IDEBIT_ISSLANG'] != 'fr') ||
            $params['IDEBIT_VERSION'] != 1 ||
            !$this->_iDebitluhn($params['IDEBIT_TRACK2'])
        ) {
            if (!preg_match($regexp, $params['IDEBIT_ISSCONF'], $conftrash)) {
                $this->getCheckoutHelper()->log(
                    "IDEBIT_ISSCONF failed regex " . print_r($conftrash, 1)
                );
            }
            if (!$this->_iDebitluhn($params['IDEBIT_TRACK2'])) {
                $this->getCheckoutHelper()->log("IDEBIT_TRACK2 failed luhn ");
            }

            if (strlen($params['IDEBIT_ISSCONF']) > 15 || strlen($params['IDEBIT_ISSCONF']) < 1) {
                $this->getCheckoutHelper()->log("IDEBIT_ISSCONF failed length ");
            }

            return false;
        } else {
            $this->getCheckoutHelper()->log("no invalid characters found ");
            return true;
        }
    }

    private function testFundedFieldLengths($params)
    {
        if (isset($params['IDEBIT_MERCHDATA']) && strlen($params['IDEBIT_MERCHDATA']) > 1024) {
            $this->getCheckoutHelper()->log(
                __METHOD__ . __LINE__ .
                " IDEBIT_MERCHDATA is too long " .
                strlen($params["IDEBIT_TRACK2"]) . " " . $params["IDEBIT_TRACK2"]
            );

            return false;
        }

        if (isset($params["IDEBIT_TRACK2"]) && (strlen($params["IDEBIT_TRACK2"]) != 37 )) {
            $this->getCheckoutHelper()->log(
                __METHOD__ . __LINE__ .
                " IDEBIT_TRACK2 is not right size " .
                strlen($params["IDEBIT_TRACK2"]) . " " . $params["IDEBIT_TRACK2"]
            );
            return false;
        }

        if (strlen($params['IDEBIT_ISSCONF']) > 15 || strlen($params['IDEBIT_ISSCONF']) < 1) {
            $this->getCheckoutHelper()->log("IDEBIT_ISSCONF failed length ");
            return false;
        }

        if (strlen($params['OLD_IDEBIT_ISSNAME']) > 30 || strlen($params['OLD_IDEBIT_ISSNAME']) < 1) {
            $this->getCheckoutHelper()->log(
                "IDEBIT_ISSNAME failed length " .
                $params['IDEBIT_ISSNAME'] . " is this long : " . strlen($params['IDEBIT_ISSNAME'])
            );
            $this->getCheckoutHelper()->log(
                "OLD IDEBIT_ISSNAME length " .
                $params['OLD_IDEBIT_ISSNAME'] . " is this long : " . strlen($params['OLD_IDEBIT_ISSNAME'])
            );
            return false;
        }
        return true;
    }

    public function _iDebitluhn($input)
    {
        $parts = explode("=", $input);
        if (count($parts) != 2) {
            return false;
        }
        return $this->luhn(trim($parts[0]));
    }

    public function luhn($input)
    {
        $sum = 0;
        $odd = strlen($input) % 2;

        // Remove any non-numeric characters.
        if (!is_numeric($input)) {
            preg_replace("/\\d+/", "", $input);
        }

        // Calculate sum of digits.
        for ($i = 0; $i < strlen($input); $i++) {
            $sum += $odd ? $input[$i] : (($input[$i] * 2 > 9) ? $input[$i] * 2 - 9 : $input[$i] * 2);
            $odd = !$odd;
        }

        // Check validity.
        return ($sum % 10 == 0) ? true : false;
    }

    public function _setOrderStatus($order)
    {
        $orderStatus = $this->status->loadDefaultByState('processing')->getStatus();
        if (!empty($orderStatus)) {
            $order->setState('processing')->setStatus($orderStatus);
            try {
                $order->save();
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
            }
        }
    }
}
