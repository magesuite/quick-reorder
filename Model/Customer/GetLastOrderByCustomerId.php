<?php
namespace MageSuite\QuickReorder\Model\Customer;

class GetLastOrderByCustomerId
{

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    public function __construct(
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    public function execute($customerId)
    {
        $customerFilter = $this->filterBuilder
            ->setField(\Magento\Sales\Api\Data\OrderInterface::CUSTOMER_ID)
            ->setValue($customerId)
            ->setConditionType('eq')
            ->create();

        $sortOrder = $this->sortOrderBuilder
            ->setField(\Magento\Sales\Api\Data\OrderInterface::ENTITY_ID)
            ->setDirection(\Magento\Framework\Api\SortOrder::SORT_DESC)
            ->create();

        $this->searchCriteriaBuilder->addFilters([$customerFilter]);
        $this->searchCriteriaBuilder->setSortOrders([$sortOrder]);
        $this->searchCriteriaBuilder->setPageSize(1)->setCurrentPage(1);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $order = $this->orderRepository->getList($searchCriteria)->getItems();

        return reset($order);
    }
}
