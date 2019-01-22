<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\Masterpass\Block\Test\Unit\Service\SoapApi;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class TransactionRefundTest extends \PHPUnit_Framework_TestCase
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
        $this->transferMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Http\TransferInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this
            ->getMockBuilder(\Moneris\Masterpass\Gateway\Config\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        if (!class_exists('Moneris_MpgTransaction')) {
            $files = glob(BP .  '/lib/Moneris*/*.php');
            foreach ($files as $f) {
                require_once $f;
            }
        }
        $xml="<?xml version=\"1.0\"?><response>"
                . "<ResponseCode>null</ResponseCode>"
                . "<ReceiptId>null</ReceiptId>"
                . "<ReferenceNum>null</ReferenceNum>"
                . "<ISO>null</ISO>"
                . "<AuthCode>null</AuthCode>"
                . "<TransTime>null</TransTime>"
                . "<TransDate>null</TransDate>"
                . "<TransType>null</TransType>"
                . "<Complete>false</Complete>"
                . "<Message>API token mismatch</Message>"
                . "<TransAmount>null</TransAmount>"
                . "<CardType>null</CardType>"
                . "<TransID>null</TransID>"
                . "<TimedOut>false</TimedOut>"
                . "<Ticket>null</Ticket>"
                . "<Refund></Refund>"
                . "</response>";
        $this->mpgRespose = new \mpgResponse($xml);
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\Masterpass\Service\SoapApi\TransactionRefund::class,
            [
                'context' => $this->contextMock,
                '_config' => $this->configMock,
            ]
        );
    }
    
    public function testCreate()
    {
        $this->transferMock
            ->method('getBody')
            ->will($this->returnValue(['type' => 'refund']));
        $this->assertTrue(is_array($this->unit->placeRequest($this->transferMock)));
    }
}