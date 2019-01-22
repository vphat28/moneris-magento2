<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\Masterpass\Block\Test\Unit\Gateway\Response;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class CaptureHandlerTest extends \PHPUnit_Framework_TestCase
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
            \Moneris\Masterpass\Gateway\Response\CaptureHandler::class,
            [
                'context' => $this->contextMock,
            ]
        );
    }
    
    public function testHandle()
    {
        $this->assertEquals(null, $this->unit->handle(['payment' => $this->paymentDataMock], []));
    }
    
}