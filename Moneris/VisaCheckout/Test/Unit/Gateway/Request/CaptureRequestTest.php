<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\VisaCheckout\Block\Test\Unit\Gateway\Request;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class CaptureRequestTest extends \PHPUnit_Framework_TestCase
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
        $this->paymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestDataBuilderMock = $this
            ->getMockBuilder(\Moneris\VisaCheckout\Helper\RequestDataBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectReaderMock = $this
            ->getMockBuilder(\Moneris\VisaCheckout\Gateway\Helper\SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['reserveOrderId', 'getReservedOrderId', 'getGrandTotal'])
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\VisaCheckout\Gateway\Request\CaptureRequest::class,
            [
                'context' => $this->contextMock,
                'requestDataBuilder' => $this->requestDataBuilderMock,
                'subjectReader' => $this->subjectReaderMock,
                'session' => $this->sessionMock,
            ]
        );
    }
    
    public function testBuild()
    {
        $this->paymentDataMock
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));
        $this->sessionMock
            ->method('getQuote')
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock
            ->method('reserveOrderId')
            ->will($this->returnValue($this->quoteMock));
        $this->assertEquals([
            'type' => 'vdotme_purchase',
            'order_id' => null,
            'amount' => '0.00',
            'callid' => null,
            'crypt_type' => 7,         
        ], $this->unit->build(['payment' => $this->paymentDataMock]));
    }
}