<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\Masterpass\Block\Test\Unit\Service;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class SoapApiTest extends \PHPUnit_Framework_TestCase
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
        $this->transferBuilderMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Http\TransferBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\Masterpass\Service\SoapApi::class,
            [
                'context' => $this->contextMock,
                'transferBuilder' => $this->transferBuilderMock,
            ]
        );
    }
    
    public function testCreate()
    {
        $this->transferBuilderMock
            ->method('setBody')
            ->will($this->returnValue($this->transferBuilderMock));
        $this->transferBuilderMock
            ->method('setMethod')
            ->will($this->returnValue($this->transferBuilderMock));
        $this->assertEquals(null, $this->unit->create([]));
    }
}