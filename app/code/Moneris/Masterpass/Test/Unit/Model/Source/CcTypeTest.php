<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\Masterpass\Block\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class CcTypeTest extends \PHPUnit_Framework_TestCase
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
        $this->paymentConfigMock = $this
            ->getMockBuilder(\Magento\Payment\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentConfigMock
            ->method('getCcTypes')
            ->will($this->returnValue([]));
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\Masterpass\Model\Source\CcType::class,
            [
                'context' => $this->contextMock,
                '_paymentConfig' => $this->paymentConfigMock,
            ]
        );
    }
    
    public function testGetAllowedTypes()
    {
        $this->assertEquals(['VI', 'MC', 'AE', 'DI', 'JCB', 'MI', 'DN', 'CUP'], $this->unit->getAllowedTypes());
    }
    
    public function testGetCcTypeLabelMap()
    {
        $this->assertEquals(['CUP' => 'China Union Pay'], $this->unit->getCcTypeLabelMap());
    }
    
    public function testToOptionArray()
    {
        $this->assertEquals([['value' => 'CUP', 'label' => 'China Union Pay']], $this->unit->toOptionArray());
    }
}