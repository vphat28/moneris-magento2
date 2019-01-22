<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\VisaCheckout\Block\Test\Unit\Controller\Index;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class PlaceOrderTest extends \PHPUnit_Framework_TestCase
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
        $this->requestMock = $this
            ->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $this->checkoutSessionMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getIncrementId', 'getStatus'])
            ->getMock();
        $this->quoteManagementMock = $this
            ->getMockBuilder(\Magento\Quote\Model\QuoteManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock = $this
            ->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock = $this
            ->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectMock = $this
            ->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->successValidatorMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session\SuccessValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\VisaCheckout\Controller\Index\PlaceOrder::class,
            [
                'context' => $this->contextMock,
                '_request' => $this->requestMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'resultFactory' => $this->resultFactoryMock,
                'quoteManagement' => $this->quoteManagementMock,
                '_objectManager' => $this->objectManagerMock,
            ]
        );
    }
    
    public function testExecute()
    {
        $this->checkoutSessionMock
            ->method('getQuote')
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock
            ->method('getBillingAddress')
            ->will($this->returnValue($this->addressMock));
        $this->quoteMock
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));
        $this->quoteManagementMock
            ->method('submit')
            ->will($this->returnValue($this->orderMock));
        $this->objectManagerMock
            ->method('get')
            ->will($this->returnValue($this->successValidatorMock));
        $this->resultFactoryMock
            ->method('create')
            ->will($this->returnValue($this->resultRedirectMock));
        $this->assertEquals(null, $this->unit->execute());
    }
}