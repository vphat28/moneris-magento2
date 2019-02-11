<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace Moneris\VisaCheckout\Block\Test\Unit\Model\Ui;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AddressTest
 * @package CyberSource\Address\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class ConfigProviderTest extends \PHPUnit_Framework_TestCase
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
        $this->storeManagerMock = $this
            ->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeMock = $this
            ->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \Moneris\VisaCheckout\Model\Ui\ConfigProvider::class,
            [
                'context' => $this->contextMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );
    }
    
    public function testGetConfig()
    {
        $this->storeManagerMock
        ->method('getStore')
        ->will($this->returnValue($this->storeMock));
        $this->assertEquals([
            'payment' => [
                'chvisa' => [
                    'isActive' => null,
                    'title' => null,
                    'api_key' => null,
                    'isDeveloperMode' => null,
                    'success_url' => '/chvisa/index/placeorder',
                ],
            ]
        ], $this->unit->getConfig());
    }
}