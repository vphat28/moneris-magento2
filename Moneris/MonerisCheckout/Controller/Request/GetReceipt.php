<?php

namespace Moneris\MonerisCheckout\Controller\Request;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Moneris\MonerisCheckout\Helper\Data;

class GetReceipt extends Action
{
    /**
     * @var Session
     */
    protected $session;

    /** @var CustomerSession */
    private $customerSession;

    /** @var Data */
    private $data;

    public function __construct(
        Session $session,
        CustomerSession $customerSession,
        Data $data,
        Context $context
    )
    {
        $this->session = $session;
        $this->customerSession = $customerSession;
        $this->data = $data;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $body = $this->data->getReceiptData($this->getRequest()->getParam('ticket'));

        if ($body['response']['success'] === "true") {
            $result->setData([
                'success' => true,
                'data' => $body['response'],
            ]);
        } else {
            throw new LocalizedException(__('Get receipt request failed'));
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isCustomerLogged()
    {
        $customerId = $this->customerSession->getId();

        return $customerId ? true : false;
    }

    /**
     * @param $quoteId
     * @return mixed
     */
    public function getQuoteMaskIdFromQuoteId($quoteId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $tableName = $resource->getTableName('quote_id_mask');
        $query = $connection->select()->from($tableName)->where('quote_id=?', $quoteId);
        $result = $connection->fetchAll($query);

        if (is_array($result) && !empty($result)) {
            return $result[0]['masked_id'];
        }

        return null;
    }
}
