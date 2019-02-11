<?php
/**
 * Copyright © 2015 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Test\Unit\Model\Mpi;

use Magento\Framework\Xml\Security;

class TxnTest extends \PHPUnit_Framework_TestCase
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
        $this->_model = $objectManagerHelper->getObject('Moneris\CreditCard\Model\Mpi\Txn');
    }

    public function testAddressConversion()
    {
        $payment = new \Magento\Framework\DataObject(require dirname(__FILE__) . '/../../_files/test_payment.php');
        $this->_model->setPayment($payment);
        $this->_model->setAmount(99.90);

        $returnData = $this->_model->buildTransactionArray();
        $this->assertTrue(\Moneris\CreditCard\Model\Mpi\Txn::TXN_TYPE == $returnData['type']);
    }
}