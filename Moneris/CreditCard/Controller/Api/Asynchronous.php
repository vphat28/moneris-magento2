<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Api;

use Magento\Quote\Api\Data\CartInterface;

class Asynchronous extends \Moneris\CreditCard\Controller\Hosted
{
    /**
     * @var CartInterface
     */
    public $quote;

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (!empty($params) && $params['xml_response']) {
            $response = $params['xml_response'];

            $xml = simplexml_load_string($response);
            $json = json_encode($xml);
            $receipt = json_decode($json, true);

            if ($receipt && $receipt['response_order_id']) {
                $responseId = $receipt['response_order_id'];
                $responseId = explode("-", $responseId);
                $this->quote = $this->quoteRepository->get($responseId[0]);

                if ($receipt && $receipt['response_code'] && (int) $receipt['response_code'] < 50) {
                    $this->quote->setBankTransactionId($receipt['bank_transaction_id']);
                } else {
                    $this->quote->setBankTransactionId(null);
                }

                $this->quote->collectTotals()->save();
            }

            $this->quote->collectTotals()->save();
        }
    }
}
