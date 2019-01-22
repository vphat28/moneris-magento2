<?php
/**
 * Copyright © 2015 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Test\Unit\Model\Source;

use Magento\Framework\Xml\Security;

class PaymentActionTest extends \PHPUnit_Framework_TestCase
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
        $this->_model = $objectManagerHelper->getObject('Moneris\CreditCard\Model\Source\PaymentAction');
    }

    public function testClass()
    {
        $returnData = $this->_model->toOptionArray();
        $this->assertTrue(isset($returnData[\Moneris\CreditCard\Model\Source\PaymentAction::PAYMENT_ACTION_AUTH]));
    }
}