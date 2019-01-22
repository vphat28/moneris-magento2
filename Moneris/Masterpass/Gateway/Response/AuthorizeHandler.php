<?php

namespace Moneris\Masterpass\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class AuthorizeHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Response\Http $response
    ) {
        $this->logger = $logger;
        $this->response = $response;
    }
    
    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $this->logger->info("auth handler");
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }
    }
}
