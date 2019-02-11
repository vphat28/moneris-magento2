<?php

namespace Moneris\CreditCard\Block\Frontend;

use Magento\Framework\Session\SessionManagerInterface;

abstract class AbstractBlock extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;

    /**
     * @var \Moneris\CreditCard\Helper\Data
     */
    public $chHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    public $checkoutSession;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    public $quote;

    /**
     * AbstractBlock constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Cms\Model\BlockRepository $blockRepository
     * @param SessionManagerInterface $customerSession
     * @param SessionManagerInterface $checkoutSession
     * @param \Moneris\CreditCard\Helper\Data $chHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Cms\Model\BlockRepository $blockRepository,
        \Moneris\CreditCard\Helper\Data $chHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_request = $context->getRequest();
        $this->_layout = $context->getLayout();
        $this->_eventManager = $context->getEventManager();
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->blockRepository = $blockRepository;
        $this->chHelper = $chHelper;
        $this->customerSession = $context->getSession();
        $this->checkoutSession = $context->getSession();
        $this->messageManager = $messageManager;
        $this->_cache = $context->getCache();

        parent::__construct($context, $data);
    }

    public function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->getCheckoutSession()->getQuote();
        }

        return $this->quote;
    }

    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    public function getCheckoutHelper()
    {
        return $this->chHelper;
    }
}
