<?php
/**
 * Copyright © 2017 CollinsHarper. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace Moneris\VisaCheckout\Gateway\Response;

use Moneris\VisaCheckout\Gateway\Helper\SubjectReader;
use Moneris\VisaCheckout\Gateway\Http\Client\Client;
use Moneris\VisaCheckout\Gateway\Http\TransferFactory;
use Moneris\VisaCheckout\Helper\RequestDataBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;

class AuthorizeResponseHandler extends AbstractResponseHandler implements HandlerInterface
{
    /**
     * @var RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * AuthorizeResponseHandler constructor.
     * @param RequestDataBuilder $requestDataBuilder
     * @param Client $client
     * @param TransferFactory $transferFactory
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        RequestDataBuilder $requestDataBuilder,
        Client $client,
        TransferFactory $transferFactory,
        SubjectReader $subjectReader,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->requestDataBuilder = $requestDataBuilder;
        $this->client = $client;
        $this->transferFactory = $transferFactory;

        parent::__construct($subjectReader, $logger);
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $this->_logger->info('auth handler response: '.print_r($response, 1));
        if (!empty($response['response'])) {
            $response = $response['response'];
        }
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $this->getValidPaymentInstance($handlingSubject);
        $payment = $this->handleResponse($payment, $response->responseData);
        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending(true);
    }
}
