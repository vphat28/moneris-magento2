<?php

namespace Moneris\MonerisCheckout\Controller\Request;


use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\Session as CustomerSession;

class GetQuoteData extends Action
{
    /**
     * @var Session
     */
    protected $session;

    /** @var CustomerSession */
    private $customerSession;

    public function __construct(
        Session $session,
        CustomerSession $customerSession,
        Context $context
    )
    {
        $this->session = $session;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $quoteId = $this->session->getQuoteId();

        if (!empty($quoteId) && !$this->isCustomerLogged()) {
            $quoteId = $this->getQuoteMaskIdFromQuoteId($quoteId);
        }

        $resultPage->setData([
            'quoteId' => $quoteId,
            'isLogged' => $this->isCustomerLogged(),
        ]);

        return $resultPage;
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
