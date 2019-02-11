<?php

namespace Moneris\Masterpass\Service;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class SoapApi
 */
class SoapApi implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    
    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    /**
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->logger = $logger;
    }
    
    /**
     *
     * @param array $request
     */
    public function create(array $request)
    {
        $this->logger->info("create in SoapAPI");
        $this->logger->info(print_r($request, 1));
        return $this->transferBuilder
            ->setBody($request)
            ->setMethod('POST')
            ->build();
    }
}
