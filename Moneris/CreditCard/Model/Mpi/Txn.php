<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Moneris\CreditCard\Model\Mpi;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Encryption\Encryptor;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Txn extends \Moneris\CreditCard\Model\Mpi
{
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Moneris\CreditCard\Helper\Data $helper,
        \Moneris\CreditCard\Model\Vault $modelVault,
        SessionManagerInterface $customerSession,
        Encryptor $encryptor,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->logger = $context->getLogger();
        parent::__construct(
            $context,
            $registry,
            $helper,
            $modelVault,
            $customerSession,
            $encryptor,
            $countryFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }
    
    /**
     * Checks if the card is enrolled in VBV/MCSC. If so,
     * sets authentication to happen. Returns the crypt type to be used
     * for the transaction.
     * If a form is returned, it is set in to the session.
     *
     * @return string $cryptType
     */
    public function fetchCryptType()
    {
        $payment = $this->getPayment();
        $order = $payment->getOrder();

        $this->getHelper()->getCheckoutSession()->setMonerisccQuoteId($order->getQuoteId());

        $mpiResponse = $this->post();
        $cryptType = $this->_interpretMpiResponse($mpiResponse, $payment);
        $this->getHelper()->getCheckoutSession()->setCryptType($cryptType);

        return $cryptType;
    }

    /**
     * @param $mpiResponse
     * @param $payment
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _interpretMpiResponse($mpiResponse, $payment)
    {
        $this->logger->info('mpiResponse = '.print_r($mpiResponse, 1));
        $mpiMessage = null;
        if ($mpiResponse->getMpiResponseData()) {
            $mpiMessage = $mpiResponse->getMpiMessage();
        }

        $orderIncrementId = $payment->getOrder()->getIncrementId();
        $this->getHelper()->getCheckoutSession()->unsMonerisccMpiForm();

        switch ($mpiMessage) {
            case self::CRYPT_RESP_N:
                // card/issuer is not enrolled; proceed with transaction as usual?
                // Visa: merchant NOT liable for chargebacks
                // Mastercard: merchant IS liable for chargebacks
                $this->getHelper()->getCheckoutSession()->setMonerisccCancelMessage('Cardholder Not Participating');
                $this->getHelper()->getCheckoutSession()->setMonerisccOrderId($orderIncrementId);
                $cryptType = self::CRYPT_SIX;
                break;
            case self::CRYPT_RESP_U:
                // card type does not participate
                // merchant IS liable for chargebacks
                $this->getHelper()->getCheckoutSession()->setMonerisccCancelMessage('Unable to Verify Enrollment');
                $this->getHelper()->getCheckoutSession()->setMonerisccOrderId($orderIncrementId);
                $cryptType = self::CRYPT_SEVEN;
                break;
            case self::CRYPT_RESP_Y:
                // card is enrolled; the included form should be displayed for user authentication
                $form = $mpiResponse->getMpiInLineForm();
                $this->getHelper()->getCheckoutSession()->setMonerisccMpiForm($form);
                $this->getHelper()->getCheckoutSession()->setMonerisccOrderId($orderIncrementId);

                // crypt type will depend on the PaRes, but use 5 to signal enrollment
                $cryptType = self::CRYPT_FIVE;

                // abuse the additional_information field by making it hold the cryptType for capture ...
                $payment->setAdditionalInformation(['crypt' => $cryptType]);

                break;
            case self::CRYPT_RESP_NULL:
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Moneris endpoint is not responding.')
                );
            default:
                $cryptType = self::CRYPT_SEVEN;
                $this->getHelper()->getCheckoutSession()->setMonerisccOrderId($orderIncrementId);
                break;
        }

        return $cryptType;
    }

    /**
     * @return array
     */
    public function buildTransactionArray()
    {
        $payment = $this->getPayment();
        $amount = $this->getAmount();

        // must be exactly 20 alphanums
        $xid = sprintf("%'920d", rand());

        $expiry = $this->getFormattedExpiry($payment);
        $merchantUrl = $this->getHelper()->getUrl(self::RETURN_URL_PATH, ['_secure' => true]);

        $md = htmlspecialchars(
            http_build_query(
                [
                    'xid'       => $xid,
                    'expiry'    => $expiry,
                    'amount'    => $amount,
                    'pan'       => $payment->getCcNumber()
                ]
            )
        );

        $txnArray = [
            'type'          => self::TXN_TYPE,
            'xid'           => $xid,
            'amount'        => $amount,
            'pan'           => $payment->getCcNumber(),
            'expdate'       => $expiry,
            'MD'            => $md,
            'merchantUrl'   => $merchantUrl,
            'accept'        => getenv('HTTP_ACCEPT'),
            'userAgent'     => getenv('HTTP_USER_AGENT')
        ];
        
        return $txnArray;
    }
}
