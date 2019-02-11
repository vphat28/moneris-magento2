<?php
/**
 * Copyright © 2015 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Test\Unit\Model;

use Magento\Framework\Xml\Security;

class AddressTest extends \PHPUnit_Framework_TestCase
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
        $this->_model = $objectManagerHelper->getObject('Moneris\CreditCard\Model\Address');
    }

    public function testAddressConversion()
    {
        $addressObj = new \Magento\Framework\DataObject(require dirname(__FILE__) . '/../_files/test_address.php');
        $this->assertTrue(count($this->_model->objToArray($addressObj)) > 1);
    }
}