<?php
namespace Moneris\Masterpass\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class RefundHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
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
        $this->logger->info('refund handler response: '.print_r($response, 1));
        if (!empty($response['response'])) {
            $response = $response['response'];
        }
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $handlingSubject['payment']->getPayment();
        $payment->setTransactionId($payment->getTxnNumber());
        $payment->setIsTransactionClosed(true);
        $payment->setIsTransactionPending(false);
    }
}
