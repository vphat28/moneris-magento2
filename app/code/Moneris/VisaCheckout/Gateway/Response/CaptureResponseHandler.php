<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CaptureResponseHandler extends AbstractResponseHandler implements HandlerInterface
{
    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $this->_logger->info('capture handler response: '.print_r($response, 1));
        if (!empty($response['response'])) {
            $response = $response['response'];
        }
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $this->getValidPaymentInstance($handlingSubject);
        $payment = $this->handleResponse($payment, $response->responseData);
        $payment->setIsTransactionClosed(true);
        $payment->setIsTransactionPending(false);
    }
}
