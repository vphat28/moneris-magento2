<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\VisaCheckout\Block\Test\Unit\Gateway\Config;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;
    
    /**
     *
     * @var Moneris\Masterpass\Block\Form
     */
    private $unit;
    
    protected function setUp()
    {
        $this->contextMock = $this
            ->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentDataMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\VisaCheckout\Gateway\Config\Config::class,
            [
                'context' => $this->contextMock,
            ]
        );
    }
    
    public function testIsActive()
    {
        $this->assertEquals(null, $this->unit->isActive());
    }
    
    public function testGetTitle()
    {
        $this->assertEquals(null, $this->unit->getTitle());
    }
    
    public function testIsTest()
    {
        $this->assertEquals(null, $this->unit->isTest());
    }
    
    public function testGetTransactionKey()
    {
        $this->assertEquals(null, $this->unit->getTransactionKey());
    }
    
    public function testGetCCTypes()
    {
        $this->assertEquals(null, $this->unit->getCCTypes());
    }
    
    public function testGetPaymentAction()
    {
        $this->assertEquals(null, $this->unit->getPaymentAction());
    }
    
    public function testGetSpecificCountry()
    {
        $this->assertEquals(null, $this->unit->getSpecificCountry());
    }
    
    public function testIsAuthMode()
    {
        $this->assertEquals(null, $this->unit->isAuthMode());
    }
    
    public function testIsDeveloperMode()
    {
        $this->assertEquals('false', $this->unit->isDeveloperMode());
    }
}