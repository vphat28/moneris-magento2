<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Helper;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Moneris\VisaCheckout\Gateway\Config\Config;

class RequestDataBuilder
{
    const PAYMENT_SOLUTION = 'visacheckout';

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * RequestDataBuilder constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param SessionManagerInterface $customerSession
     * @param SessionManagerInterface $checkoutSession
     * @param CheckoutHelper $checkoutHelper
     * @param Config $gatewayConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        SessionManagerInterface $customerSession,
        SessionManagerInterface $checkoutSession,
        CheckoutHelper $checkoutHelper,
        Config $gatewayConfig
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param $callId
     * @param $quoteId
     * @return object
     */
    public function buildVisaDecryptRequestData($callId, $quoteId)
    {
        $request = new \stdClass();

        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->merchantReferenceCode = $quoteId;

        $getVisaCheckoutDataService = new \stdClass();
        $getVisaCheckoutDataService->run = "true";
        $request->getVisaCheckoutDataService =  $getVisaCheckoutDataService;

        $request->paymentSolution = self::PAYMENT_SOLUTION;

        $vc = new \stdClass();
        $vc->orderID = $callId;

        $request->vc = $vc;

        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     */
    public function buildAuthorizationRequestData(\Magento\Payment\Model\InfoInterface $payment)
    {
        $quote = $this->checkoutSession->getQuote();

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->merchantReferenceCode = $quote->getId();

        $ccAuthService = new \stdClass();
        $ccAuthService->run = "true";
        $request->ccAuthService = $ccAuthService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($quote->getGrandTotal());
        $request->purchaseTotals = $purchaseTotals;

        $request->paymentSolution = self::PAYMENT_SOLUTION;

        $vc = new \stdClass();
        $vc->orderID = $payment->getAdditionalInformation("callId");
        $request->vc = $vc;

        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     */
    public function buildCaptureRequestData(\Magento\Payment\Model\InfoInterface $payment)
    {
        $merchantReferenceCode = $payment->getAdditionalInformation('merchantReferenceCode');
        $currency = $payment->getAdditionalInformation('currency');
        $amount = $payment->getAdditionalInformation('amount');

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->merchantReferenceCode = $merchantReferenceCode;

        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = "true";
        $ccCaptureService->authRequestID = $payment->getAdditionalInformation("requestID");
        $request->ccCaptureService = $ccCaptureService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $currency;
        $purchaseTotals->grandTotalAmount = $this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $request->paymentSolution = self::PAYMENT_SOLUTION;
        $request->orderRequestToken = $payment->getAdditionalInformation("requestToken");

        $vc = new \stdClass();
        $vc->orderID = $payment->getAdditionalInformation("callID");
        $request->vc = $vc;

        return $request;
    }

    /**
     * @return \stdClass
     */
    public function buildSettlementRequestData()
    {
        $request = new \stdClass();

        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = "true";
        $request->ccCaptureService = $ccCaptureService;

        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     */
    public function buildVoidRequestData(\Magento\Payment\Model\InfoInterface $payment)
    {
        $merchantReferenceCode = $payment->getAdditionalInformation('merchantReferenceCode');

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->merchantReferenceCode = $merchantReferenceCode;

        $voidService = new \stdClass();
        $voidService->run = "true";
        $voidService->voidRequestID = $payment->getAdditionalInformation("requestID");
        $request->voidService = $voidService;

        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return \stdClass
     */
    public function buildRefundRequestData(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $merchantReferenceCode = $payment->getAdditionalInformation('merchantReferenceCode');
        $currency = $payment->getAdditionalInformation('currency');

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->merchantReferenceCode = $merchantReferenceCode;

        $ccCreditService = new \stdClass();
        $ccCreditService->run = "true";
        $request->ccCreditService = $ccCreditService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $currency;
        $purchaseTotals->grandTotalAmount = $this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $request->paymentSolution = self::PAYMENT_SOLUTION;

        $vc = new \stdClass();
        $vc->orderID = $payment->getAdditionalInformation("callID");
        $request->vc = $vc;

        $order = $payment->getOrder();
        $request->billTo = $this->buildBillTo($order->getBillingAddress());
        $request->card = $this->buildCard($payment);

        return $request;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $billingAddress
     * @return \stdClass
     */
    private function buildBillTo(\Magento\Sales\Model\Order\Address $billingAddress)
    {
        $billTo = new \stdClass();
        $billTo->city =  $billingAddress->getData('city');
        $billTo->country = $billingAddress->getData('country_id');
        $billTo->postalCode = $billingAddress->getData('postcode');
        $billTo->state = $billingAddress->getRegionCode();
        $billTo->street1 = $billingAddress->getData('street');
        $billTo->email = $billingAddress->getEmail();
        $billTo->firstName = $billingAddress->getFirstname();
        $billTo->lastName = $billingAddress->getLastname();

        return $billTo;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     */
    private function buildCard(\Magento\Payment\Model\InfoInterface $payment)
    {
        $card = new \stdClass();
        $card->accountNumber = $payment->getAdditionalInformation("accountNumber");
        $card->cardType = $payment->getAdditionalInformation("cardType");
        $card->expirationMonth = $payment->getAdditionalInformation("expirationMonth");
        $card->expirationYear = $payment->getAdditionalInformation("expirationYear");

        return $card;
    }
    
    /**
     * @param float $amount
     * @return string
     */
    private function formatAmount($amount)
    {
        if (!is_float($amount)) {
            $amount = (float) $amount;
        }
        
        return number_format($amount, 2, '.', '');
    }
}
