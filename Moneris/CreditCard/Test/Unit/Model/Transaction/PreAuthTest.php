<?php
/**
 * Copyright © 2015 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Test\Unit\Model\Transaction;

use Magento\Framework\Xml\Security;

class PreAuthTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Model
     *
     * @var \Moneris\CreditCard\Model\Transaction
     */
    protected $_model;

    public function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_model = $objectManagerHelper->getObject('Moneris\CreditCard\Model\Transaction\PreAuth');
        $customerSession = $objectManagerHelper->getObject('Magento\Customer\Model\Session');
        $this->_model->getHelper()->setCustomerSession($customerSession);
        $this->_model->getHelper()->setMockData(require dirname(__FILE__) . '/../../_files/test_config.php');
    }

    public function testPreAuth()
    {
        $this->_model->getHelper()->setMockData(require dirname(__FILE__) . '/../../_files/test_config.php');

        $addressObj = new \Magento\Framework\DataObject(require dirname(__FILE__) . '/../../_files/test_address.php');
        $payment = new \Magento\Framework\DataObject(require dirname(__FILE__) . '/../../_files/test_payment.php');
        $order = new \Magento\Framework\DataObject(require dirname(__FILE__) . '/../../_files/test_order.php');
        $orderCurrency = new \Magento\Framework\DataObject(require dirname(__FILE__) . '/../../_files/test_order.php');
        $orderCurrency->setCurrencyCode('USD');

        $order->setOrderCurrency($orderCurrency);
        $order->setBillingAddress($addressObj);
        $payment->setOrder($order);

        $this->_model->setPayment($payment);
        $this->_model->setAmount(1.00);
        $returnData = $this->_model->post();
        print_r($returnData);

        //echo " helper  cust data ? " . $this->_model->getHelper()->getCustomerSession()->getCustomerId() ."<br/>\n";
        //echo "receipt data " . $this->_model->getHelper()->getCustomerSession()->getMoneriscccData() ."<br/>\n";

        $this->assertTrue(count($this->_model->objToArray($addressObj)) > 1);
    }
}