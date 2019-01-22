<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\VisaCheckout\Block\Test\Unit\Gateway\Command;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class CaptureStrategyCommandTest extends \PHPUnit_Framework_TestCase
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
        $this->subjectReaderMock = $this
            ->getMockBuilder(\Moneris\VisaCheckout\Gateway\Helper\SubjectReader::class)
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
        $this->filterBuilderMock = $this
            ->getMockBuilder(\Magento\Framework\Api\FilterBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock = $this
            ->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaMock = $this
            ->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionRepositoryMock = $this
            ->getMockBuilder(\Magento\Sales\Api\TransactionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment\Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commandPoolMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commandPoolMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commandMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Command\GatewayCommand::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\VisaCheckout\Gateway\Command\CaptureStrategyCommand::class,
            [
                'context' => $this->contextMock,
                'subjectReader' => $this->subjectReaderMock,
                'filterBuilder' => $this->filterBuilderMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'transactionRepository' => $this->transactionRepositoryMock,
                'commandPool' => $this->commandPoolMock,
            ]
        );
    }
    
    public function testExecute()
    {
        $this->subjectReaderMock
            ->method('readPayment')
            ->will($this->returnValue($this->paymentDataMock));
        $this->paymentDataMock
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));
        $this->filterBuilderMock
            ->method('setField')
            ->will($this->returnValue($this->filterBuilderMock));
        $this->filterBuilderMock
            ->method('setValue')
            ->will($this->returnValue($this->filterBuilderMock));
        $this->searchCriteriaBuilderMock
            ->method('create')
            ->will($this->returnValue($this->searchCriteriaMock));
        $this->transactionRepositoryMock
            ->method('getList')
            ->will($this->returnValue($this->transactionMock));
        $this->commandPoolMock
            ->method('get')
            ->will($this->returnValue($this->commandMock));
        $this->assertEquals(null, $this->unit->execute(['payment' => $this->paymentDataMock]));
    }
}