<?php
namespace MageSuite\QuickReorder\Test\Unit\CustomerData;

class ReorderBannerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Sales\Helper\Reorder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reorderHelperStub;

    /**
     * @var \MageSuite\QuickReorder\Helper\Configuration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationStub;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionStub;

    /**
     * @var \MageSuite\QuickReorder\Model\Customer\GetLastOrderByCustomerId|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $getLastOrderByCustomerIdStub;

    /**
     * @var \Magento\Customer\Model\Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerStub;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderStub;

    /**
     * @var \MageSuite\QuickReorder\CustomerData\ReorderBanner
     */
    protected $reorderBannerDataSection;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();

        $this->reorderHelperStub = $this->getMockBuilder(\Magento\Sales\Helper\Reorder::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed'])
            ->getMock();

        $this->configurationStub = $this->getMockBuilder(\MageSuite\QuickReorder\Helper\Configuration::class)
            ->disableOriginalConstructor()
            ->setMethods(['isReorderBannerEnabled'])
            ->getMock();

        $this->customerSessionStub = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn', 'getCustomer'])
            ->getMock();
        $this->getLastOrderByCustomerIdStub = $this->getMockBuilder(\MageSuite\QuickReorder\Model\Customer\GetLastOrderByCustomerId::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();

        $this->customerStub = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getFirstname'])
            ->getMock();
        $this->orderStub = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getBaseGrandTotal', 'getTotalQtyOrdered', 'getItems'])
            ->getMock();

        $this->customerSessionStub->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerStub);

        $this->reorderBannerDataSection = $this->objectManager->create(
            \MageSuite\QuickReorder\CustomerData\ReorderBanner::class,
            [
                'reorderHelper' => $this->reorderHelperStub,
                'customerSession' => $this->customerSessionStub,
                'configuration' => $this->configurationStub,
                'getLastOrderByCustomerId' => $this->getLastOrderByCustomerIdStub
            ]
        );
    }

    public function testGetSectionDataForDisabledReorder()
    {
        $expectedResult = [];

        $this->reorderHelperStub->expects($this->once())
            ->method('isAllowed')
            ->willReturn(false);

        $this->assertEquals($expectedResult, $this->reorderBannerDataSection->getSectionData());
    }

    public function testGetSectionDataForNotLoggedIn()
    {
        $expectedResult = [];

        $this->reorderHelperStub->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->configurationStub->expects($this->once())
            ->method('isReorderBannerEnabled')
            ->willReturn(true);

        $this->customerSessionStub->expects($this->atMost(1))
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->assertEquals($expectedResult, $this->reorderBannerDataSection->getSectionData());
    }

    public function testGetSectionDataWithoutLastOrder()
    {
        $expectedResult = [];

        $this->reorderHelperStub->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->configurationStub->expects($this->once())
            ->method('isReorderBannerEnabled')
            ->willReturn(true);

        $this->customerSessionStub->expects($this->atMost(1))
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->getLastOrderByCustomerIdStub->expects($this->any())
            ->method('execute')
            ->willReturn(null);

        $this->assertEquals($expectedResult, $this->reorderBannerDataSection->getSectionData());
    }

    public function testGetSectionData()
    {
        $expectedResult = [
            'firstname' => 'John',
            'lastOrderAmount' => '$34.54',
            'lastOrderItemsCount' => 2,
            'lastOrderItems' => [
                [
                    'name' => 'test1',
                    'count' => 1
                ],
                [
                    'name' => 'test2',
                    'count' => 1
                ]
            ],
            'lastOrderReorderLink' => 'http://localhost/index.php/sales/order/reorder/order_id/1/',
            'lastOrderViewLink' => 'http://localhost/index.php/sales/order/view/order_id/1/',
        ];

        $this->reorderHelperStub->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->configurationStub->expects($this->once())
            ->method('isReorderBannerEnabled')
            ->willReturn(true);

        $this->customerSessionStub->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->getLastOrderByCustomerIdStub->expects($this->any())
            ->method('execute')
            ->willReturn($this->orderStub);

        $this->orderStub->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->customerStub->expects($this->once())
            ->method('getFirstname')
            ->willReturn('John');

        $this->orderStub->expects($this->once())
            ->method('getBaseGrandTotal')
            ->willReturn('34.54');

        $this->orderStub->expects($this->once())
            ->method('getTotalQtyOrdered')
            ->willReturn(2);

        $this->orderStub->expects($this->once())
            ->method('getItems')
            ->willReturn([
                new \Magento\Framework\DataObject(['name' => 'test1', 'qty_ordered' => 1]),
                new \Magento\Framework\DataObject(['name' => 'test2', 'qty_ordered' => 1])
            ]);

        $this->assertEquals($expectedResult, $this->reorderBannerDataSection->getSectionData());
    }
}
