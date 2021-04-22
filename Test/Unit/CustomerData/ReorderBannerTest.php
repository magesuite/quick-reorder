<?php

namespace Creativestyle\MageSuiteQuickReorder\Test\Unit\CustomerData;

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
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionStub;

    /**
     * @var \Creativestyle\MageSuiteQuickReorder\Model\Customer\GetLastOrderByCustomerId|\PHPUnit_Framework_MockObject_MockObject
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
     * @var \Creativestyle\MageSuiteQuickReorder\CustomerData\ReorderBanner
     */
    protected $reorderBannerDataSection;

    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();

        $this->reorderHelperStub = $this->getMockBuilder(\Magento\Sales\Helper\Reorder::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed'])
            ->getMock();
        $this->customerSessionStub = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn', 'getCustomer'])
            ->getMock();
        $this->getLastOrderByCustomerIdStub = $this->getMockBuilder(\Creativestyle\MageSuiteQuickReorder\Model\Customer\GetLastOrderByCustomerId::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();

        $this->customerStub = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getFirstname'])
            ->getMock();
        $this->orderStub = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getBaseGrandTotal', 'getTotalQtyOrdered'])
            ->getMock();

        $this->customerSessionStub->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerStub);

        $this->reorderBannerDataSection = $this->objectManager->create(
            \Creativestyle\MageSuiteQuickReorder\CustomerData\ReorderBanner::class,
            [
                'reorderHelper' => $this->reorderHelperStub,
                'customerSession' => $this->customerSessionStub,
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

        $this->customerSessionStub->expects($this->once())
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

        $this->customerSessionStub->expects($this->once())
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
            'lastOrderReorderLink' => 'http://localhost/index.php/sales/order/reorder/order_id/1/',
            'lastOrderViewLink' => 'http://localhost/index.php/sales/order/view/order_id/1/',
        ];

        $this->reorderHelperStub->expects($this->once())
            ->method('isAllowed')
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

        $this->assertEquals($expectedResult, $this->reorderBannerDataSection->getSectionData());
    }
}
