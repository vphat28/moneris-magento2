<?php
/**
 * Copyright © 2015 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Test\Unit\Model;

use Magento\Framework\Xml\Security;

class ExceptionTest extends \PHPUnit_Framework_TestCase
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
        $this->_model = $objectManagerHelper->getObject('Moneris\CreditCard\Model\Exception');
    }

    public function testClass()
    {
        $this->assertTrue(get_class($this->_model) == 'Moneris\CreditCard\Model\Exception');
    }
}