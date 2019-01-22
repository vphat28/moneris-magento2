<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\Masterpass\Block\Test\Unit\Controller\Index;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class PaymentTest extends \PHPUnit_Framework_TestCase
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
        $this->urlMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionMock = $this
            ->getMockBuilder(\Moneris\Masterpass\Service\Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactoryMock = $this
            ->getMockBuilder(\Magento\Framework\Controller\Result\JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this
            ->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        if (!class_exists('Moneris_MpgTransaction')) {
            $files = glob(BP .  '/lib/Moneris*/*.php');
            foreach ($files as $f) {
                require_once $f;
            }
        }
        $xml="<?xml version=\"1.0\"?><response>"
                . "<MPRedirectUrl>test</MPRedirectUrl>"
                . "<ResponseCode>001</ResponseCode>"
                . "<MPRequestToken>req_token</MPRequestToken>"
                . "</response>";
        $this->mpgRespose = new \mpgResponse($xml);
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\Masterpass\Controller\Index\Payment::class,
            [
                'context' => $this->contextMock,
                '_request' => $this->requestMock,
                'resultJsonFactory' => $this->jsonFactoryMock,
                '_url' => $this->urlMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'transaction' => $this->transactionMock,
            ]
        );
    }
    
    public function testExecute()
    {
        $this->checkoutSessionMock
            ->method('getQuote')
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock
            ->method('getShippingAddress')
            ->will($this->returnValue($this->addressMock));
        $this->transactionMock
            ->method('mpgRequest')
            ->will($this->returnValue($this->mpgRespose));
        $this->jsonFactoryMock
            ->method('create')
            ->will($this->returnValue($this->jsonMock));
        $this->assertEquals($this->jsonMock, $this->unit->execute());
    }
}