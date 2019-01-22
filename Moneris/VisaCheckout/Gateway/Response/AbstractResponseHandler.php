<?php

namespace Moneris\VisaCheckout\Gateway\Response;

use Moneris\VisaCheckout\Gateway\Helper\SubjectReader;
use Moneris\VisaCheckout\Gateway\Validator\ResponseCodeValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\ContextHelper;

abstract class AbstractResponseHandler
{
    const REQUEST_ID = "requestID";
    const REASON_CODE = "reasonCode";
    const DECISION = "decision";
    const MERCHANT_REFERENCE_CODE = "merchantReferenceCode";
    const REQUEST_TOKEN = "requestToken";
    const RECONCILIATION_ID = "reconciliationID";
    const CALL_ID = "callID";
    const CURRENCY = "currency";
    const AMOUNT = "amount";
    const CARD_ACCOUNT_NUMBER = "accountNumber";
    const CARD_TYPE = "cardType";
    const CARD_EXP_MONTH = "expirationMonth";
    const CARD_EXP_YEAR = "expirationYear";

    private $cardTypes = [
        "AMEX" => "003",
        "DISCOVER" => "004",
        "MASTERCARD" => "002",
        "VISA" => "001",
    ];

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    
    /**
     * AbstractResponseHandler constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->subjectReader = $subjectReader;
        $this->_logger = $logger;
    }

    /**
     * @param array $buildSubject
     * @return \Magento\Payment\Model\InfoInterface
     */
    protected function getValidPaymentInstance(array $buildSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $paymentDO->getPayment();

        ContextHelper::assertOrderPayment($payment);

        return $payment;
    }

    protected function handleResponse($payment, $response, $responseDecrypted = null)
    {
        $this->_logger->info("start handle response");
        $this->_logger->info("response = ".print_r($response, 1));

        $payment->unsAdditionalInformation();

        $payment->setTransactionId($response['ReferenceNum']);
        $payment->setAdditionalInformation(self::REQUEST_ID, $response['ReceiptId']);
        $payment->setAdditionalInformation(self::AMOUNT, $response['TransAmount']);
        $payment->setAdditionalInformation(self::REASON_CODE, $response['ResponseCode']);
        $payment->setAdditionalInformation(self::DECISION, $response['Message']);
        $payment->setAdditionalInformation(self::MERCHANT_REFERENCE_CODE, $response['ReferenceNum']);
        $payment->setAdditionalInformation('txn_number', $response['TransID']);
        $this->_logger->info("end handle response");
        return $payment;
    }
}
