<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\Masterpass\Block\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class FormTest extends \PHPUnit_Framework_TestCase
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
            ->getMockBuilder(\Magento\Framework\View\Element\Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->gatewayConfig = $this
            ->getMockBuilder(\Moneris\Masterpass\Gateway\Config\Config::class)
            ->setMethods([
                'getAvailableCardTypes', 
                'getCountryAvailableCardTypes',
                'isCvvEnabled',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->ccType = $this
            ->getMockBuilder(\Moneris\Masterpass\Model\Source\CcType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionQuote = $this
            ->getMockBuilder(\Magento\Backend\Model\Session\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\Masterpass\Block\Form::class,
            [
                'context' => $this->contextMock,
                'gatewayConfig' => $this->gatewayConfig,
                'ccType' => $this->ccType,
                'sessionQuote' => $this->sessionQuote,
            ]
        );
    }
    
    public function testGetCcAvailableTypes()
    {
        $this->gatewayConfig
            ->method('getAvailableCardTypes')
            ->will($this->returnValue([]));
        $this->gatewayConfig
            ->method('getCountryAvailableCardTypes')
            ->will($this->returnValue([1]));
        $this->ccType
            ->method('getCcTypeLabelMap')
            ->will($this->returnValue([]));
        $this->sessionQuote
            ->method('getQuote')
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock
            ->method('getBillingAddress')
            ->will($this->returnValue($this->addressMock));
        $this->assertEquals([], $this->unit->getCcAvailableTypes());
    }
    
    public function testUseCvv()
    {
        $this->assertEquals(null, $this->unit->useCvv());
    }
    
    public function testIsVaultEnabled()
    {
        $this->assertEquals(false, $this->unit->isVaultEnabled());
    }
}