<?php
namespace MageSuite\QuickReorder\Model\ResourceModel;

class LatestOrderItem extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb implements LatestOrderItemResourceInterface
{
    protected function _construct()
    {
        $this->_setMainTable(self::SALES_ORDER_ITEM_TABLE);
    }

    public function getCustomerLatestOrderItems($customerId, $statuses, $count)
    {
        $connection = $this->getConnection();
        $subQuery = $connection->select()
            ->from(['soi' => self::SALES_ORDER_ITEM_TABLE], 'MAX(soi.item_id)')
            ->join(['so' => self::SALES_ORDER_TABLE], 'soi.order_id = so.entity_id', '')
            ->where('soi.parent_item_id IS NULL')
            ->where('so.customer_id = ?', $customerId)
            ->where('so.status IN (?)', $statuses)
            ->group('product_id')
            ->order(new \Zend_Db_Expr('NULL'));
        $query = $connection->select()
            ->from(self::SALES_ORDER_ITEM_TABLE, ['product_id', 'product_options'])
            ->where('item_id IN(?)', new \Zend_Db_Expr("SELECT * FROM ({$subQuery->__toString()}) AS subquery"))
            ->order('item_id DESC')
            ->limit($count);
        return $connection->fetchAssoc($query);
    }
}
