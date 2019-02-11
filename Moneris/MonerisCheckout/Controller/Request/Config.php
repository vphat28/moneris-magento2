<?php

namespace Moneris\MonerisCheckout\Controller\Request;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Store\Model\StoreManagerInterface;

class Config extends Action
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Session
     */
    protected $session;

    /** @var UserContextInterface */
    private $userContext;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /** @var QuoteRepository */
    private $quoteRepository;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Session $session,
        ScopeConfigInterface $scopeConfig,
        UserContextInterface $userContext,
        QuoteRepository $quoteRepository
    ) {
        $this->storeManager = $storeManager;
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
        $this->userContext = $userContext;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
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

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $quoteId = $this->session->getQuoteId();

        if (!empty($quoteId) && !$this->userContext->getUserId()) {
            $quoteId = $this->getQuoteMaskIdFromQuoteId($quoteId);
        }

        $resultPage->setData([
            'currencyCode' => $currencyCode,
            'quoteId' => $quoteId,
            'api' => $this->helper->getAPIKey(),
            'countryCode' => $this->scopeConfig->getValue('general/country/default'),
            'isLogged' => $this->icHelper->isCustomerLogged(),
        ]);

        return $resultPage;
    }
}
