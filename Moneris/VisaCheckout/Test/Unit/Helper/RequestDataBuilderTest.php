<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\VisaCheckout\Block\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class RequestDataBuilderTest extends \PHPUnit_Framework_TestCase
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
            ->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->gatewayConfigMock = $this
            ->getMockBuilder(\Moneris\VisaCheckout\Gateway\Config\Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMerchantId'])
            ->getMock();
        $this->paymentInfoMock = $this
            ->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
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
            ->setMethods(['getBillingAddress'])
            ->getMock();
        $this->addressMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\VisaCheckout\Helper\RequestDataBuilder::class,
            [
                'context' => $this->contextMock,
                'gatewayConfig' => $this->gatewayConfigMock,
                'checkoutSession' => $this->checkoutSessionMock,
            ]
        );
    }
    
    public function testBuildVisaDecryptRequestData()
    {
        $data = new \stdClass();
        $data->merchantID = null;
        $data->merchantReferenceCode = 1;
        $data->getVisaCheckoutDataService = new \stdClass();
        $data->getVisaCheckoutDataService->run = 'true';
        $data->paymentSolution = 'visacheckout';
        $data->vc = new \stdClass();
        $data->vc->orderID = 1;
        $this->assertEquals($data, $this->unit->buildVisaDecryptRequestData(1, 1));
    }
    
    public function testBuildAuthorizationRequestData()
    {
        $data = new \stdClass();
        $data->merchantID = null;
        $data->merchantReferenceCode = null;
        $data->ccAuthService = new \stdClass();
        $data->ccAuthService->run = 'true';
        $data->paymentSolution = 'visacheckout';
        $data->vc = new \stdClass();
        $data->vc->orderID = null;
        $data->purchaseTotals = new \stdClass();
        $data->purchaseTotals->currency = null;
        $data->purchaseTotals->grandTotalAmount = 0;
        $this->checkoutSessionMock
        ->method('getQuote')
        ->will($this->returnValue($this->quoteMock));
        $this->assertEquals($data, $this->unit->buildAuthorizationRequestData($this->paymentInfoMock));
    }
    
    public function testBuildCaptureRequestData()
    {
        $data = new \stdClass();
        $data->merchantID = null;
        $data->merchantReferenceCode = null;
        $data->orderRequestToken = null;
        $data->ccCaptureService = new \stdClass();
        $data->ccCaptureService->run = 'true';
        $data->ccCaptureService->authRequestID = null;
        $data->paymentSolution = 'visacheckout';
        $data->vc = new \stdClass();
        $data->vc->orderID = null;
        $data->purchaseTotals = new \stdClass();
        $data->purchaseTotals->currency = null;
        $data->purchaseTotals->grandTotalAmount = 0;
        $this->checkoutSessionMock
        ->method('getQuote')
        ->will($this->returnValue($this->quoteMock));
        $this->assertEquals($data, $this->unit->buildCaptureRequestData($this->paymentInfoMock));
    }
    
    public function testBuildSettlementRequestData()
    {
        $data = new \stdClass();
        $data->ccCaptureService = new \stdClass();
        $data->ccCaptureService->run = 'true';
        $this->checkoutSessionMock
        ->method('getQuote')
        ->will($this->returnValue($this->quoteMock));
        $this->assertEquals($data, $this->unit->buildSettlementRequestData());
    }
    
    public function testBuildVoidRequestData()
    {
        $data = new \stdClass();
        $data->merchantID = null;
        $data->merchantReferenceCode = null;
        $data->voidService = new \stdClass();
        $data->voidService->run = 'true';
        $data->voidService->voidRequestID = null;
        
        $this->checkoutSessionMock
        ->method('getQuote')
        ->will($this->returnValue($this->quoteMock));
        $this->assertEquals($data, $this->unit->buildVoidRequestData($this->paymentInfoMock));
    }
    
    public function testBuildRefundRequestData()
    {
        $data = new \stdClass();
        $data->merchantID = null;
        $data->merchantReferenceCode = null;
        $data->ccCreditService = new \stdClass();
        $data->ccCreditService->run = 'true';
        $data->paymentSolution = 'visacheckout';
        $data->vc = new \stdClass();
        $data->vc->orderID = null;
        $data->purchaseTotals = new \stdClass();
        $data->purchaseTotals->currency = null;
        $data->purchaseTotals->grandTotalAmount = 0;
        $data->billTo = new \stdClass();
        $data->billTo->city = null;
        $data->billTo->country = null;
        $data->billTo->postalCode = null;
        $data->billTo->state = null;
        $data->billTo->street1 = null;
        $data->billTo->email = null;
        $data->billTo->firstName = null;
        $data->billTo->lastName = null;
        $data->card = new \stdClass();
        $data->card->accountNumber = null;
        $data->card->cardType = null;
        $data->card->expirationMonth = null;
        $data->card->expirationYear = null;
        
        $this->paymentMock
        ->method('getOrder')
        ->will($this->returnValue($this->orderMock));
        $this->checkoutSessionMock
        ->method('getQuote')
        ->will($this->returnValue($this->quoteMock));
        $this->orderMock
        ->method('getBillingAddress')
        ->will($this->returnValue($this->addressMock));
        $this->assertEquals($data, $this->unit->buildRefundRequestData($this->paymentMock, 0));
    }
}