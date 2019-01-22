<?php
/**
 * Copyright © 2015 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Test\Unit\Helper;

use Magento\Framework\Xml\Security;

class DataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * moneris helper
     *
     * @var \Moneris\CreditCard\Helper\Config
     */
    protected $_model;

    public function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->helper = $objectManagerHelper->getObject('Moneris\CreditCard\Helper\Data');
        $this->helper->setMockData(require dirname(__FILE__) . '/../_files/test_config.php');
    }

    public function testAvsCodes()
    {
        $this->assertTrue(count($this->helper->getAvsSuccessCodes()) > 0);
    }

    public function testUpdatePayment()
    {
        $payment = new \Magento\Framework\DataObject();
        $this->assertTrue($this->helper->getPaymentAdditionalInfo($payment, 'no') == null);
    }

    public function testPaymentMethod()
    {
        print_r(" SHANE comparing " . $this->helper->getPaymentAction() ."\n");
        print_r($this->helper->getMockData());
         $this->assertTrue($this->helper->getPaymentAction() == 'payment_action');
    }

    public function testIsUsApi()
    {
         $this->assertTrue($this->helper->isUsApi() == false);
    }

    public function testIsVbvRequired()
    {
         $this->assertTrue($this->helper->getIsVbvRequired() == false);
    }


    public function testMonerisStoreId()
    {
         $this->assertTrue($this->helper->getMonerisStoreId() != '');
    }

    public function testResponses()
    {
         $this->assertTrue(count($this->helper->getResponses()) > 0);

    }
}
