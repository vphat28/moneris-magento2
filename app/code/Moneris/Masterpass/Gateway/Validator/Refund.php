<?php
namespace Moneris\Masterpass\Gateway\Validator;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;

class Refund extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
    ) {
        parent::__construct($resultFactory);
        $this->logger = $logger;
        $this->response = $response;
    }

    /**
     * Handles transaction id
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }
        $response = $validationSubject['response'];
        if (!empty($response['response'])) {
            $response = $response['response'];
        }
        $this->logger->info('validator refund ' . print_r($response, 1));
        if ($response->getResponseCode() == '027') {
            return $this->createResult(
                true,
                []
            );
        } else {
            return $this->createResult(
                false,
                [__('Gateway rejected the transaction.')]
            );
        }
    }
}
