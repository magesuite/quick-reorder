<?php
namespace MageSuite\QuickReorder\Model\ResourceModel;

interface LatestOrderItemResourceInterface
{
    const SALES_ORDER_TABLE = 'sales_order';
    const SALES_ORDER_ITEM_TABLE = 'sales_order_item';

    public function getCustomerLatestOrderItems($customerId, $statuses, $count);
}
