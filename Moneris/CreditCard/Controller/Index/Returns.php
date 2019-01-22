<?php
/**
 * Copyright © 2016 Collinsharper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Controller\Index;

use Magento\Sales\Model\Order as OrderModel;
use Moneris\CreditCard\Model\Exception as MonerisException;

class Returns extends AbstractController
{
    public function execute()
    {
        $orderId = $this->_getCheckoutSession()->getMonerisccOrderId();

        if (!$orderId) {
            $errorMessage = __('We were unable to locate your order. Please contact Customer Support.');
            $this->messageManager->addError($errorMessage);
            $this->_abort();
        }

        $order = $this->getOrderByIncrementId($orderId);
        $paRes = $this->getRequest()->getParam(self::PARAM_PARES);
        $md = $this->getRequest()->getParam(self::PARAM_MD);
        if (!$order || !$paRes || !$md) {
            $this->_addOrderMessage($order, __('Failed VBV / 3DS callback parameters.'), OrderModel::STATE_CANCELED);
            $this->_abort(__("The was an error communicating with your bank. Please try again in a few minutes."));
        }
        
        $message = __('VBV / 3DS was unable to complete successfully.');
        if ($this->_getCheckoutSession()->getMonerisccCancelMessage()) {
            $message = $this->_getCheckoutSession()->getMonerisccCancelMessage();
        }
        
        try {
            $payment = $order->getPayment();
            // restore CVD from the session
            $checkoutSession = $this->_getCheckoutSession();
            $payment->setCcCid($checkoutSession->getMonerisCavvCvdResult());
            $checkoutSession->setMonerisCavvCvdResult(false);
            $this->getPaymentMethod()->cavvContinue($payment, $paRes, $md, $order);
            $returnUrl = $this->getHelper()->getUrl(self::CHECKOUT_SUCCESS_PATH);
            $this->getResponse()->setRedirect($returnUrl);
        } catch (MonerisException $me) {
            $this->_addOrderMessage($order, $message, OrderModel::STATE_CANCELED);
            $this->_abort($message);
        } catch (\Exception $e) {
            $message = __('VBV / 3DS was unable to complete successfully.');
            if ($this->_getCheckoutSession()->getMonerisccCancelMessage()) {
                $message = $this->_getCheckoutSession()->getMonerisccCancelMessage();
            }
            
            $this->_addOrderMessage($order, $message, OrderModel::STATE_CANCELED);
            $this->_abort($e->getMessage());
        }
    }
}
