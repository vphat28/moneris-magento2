<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Plugin;
 
use Magento\Framework\Exception\LocalizedException;
 
class HostedPlugin
{
    
    protected $request;
    
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Plugin constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->request = $request;
    }
 
    public function beforeExecute()
    {
        $params = $this->request->getParams();
        if (!empty($params) && $params['xml_response']) {
            $response = $params['xml_response'];
            $xml = simplexml_load_string($response);
            $json = json_encode($xml);
            $receipt = json_decode($json, true);
            
            if ($receipt && $receipt['response_order_id']) {
                $responseId = $receipt['response_order_id'];
                $responseId = explode("-", $responseId);
                $quote = $this->quoteRepository->get($responseId[0]);
                $quote->setBankTransactionId(null);
                $quote->collectTotals()->save();
            }
        }
    }
}
