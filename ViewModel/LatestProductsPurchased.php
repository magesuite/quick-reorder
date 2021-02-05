<?php
namespace MageSuite\QuickReorder\ViewModel;

class LatestProductsPurchased implements \MageSuite\QuickReorder\ViewModel\LatestProductsPurchasedInterface
{
    const SALES_ORDER_TABLE = 'sales_order';
    const SALES_ORDER_ITEM_TABLE = 'sales_order_item';

    /**
     * @var \MageSuite\ContentConstructorFrontend\Service\ProductTileRenderer
     */
    protected $productTileRenderer;

    /**
     * @var \MageSuite\QuickReorder\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item
     */
    protected $orderItemResource;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $catalogConfig;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var iterable|null
     */
    protected $products = null;

    public function __construct(
        \MageSuite\ContentConstructorFrontend\Service\ProductTileRenderer $productTileRenderer,
        \MageSuite\QuickReorder\Helper\Configuration $configuration,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Spi\OrderItemResourceInterface $orderItemResource,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->productTileRenderer = $productTileRenderer;
        $this->configuration = $configuration;
        $this->customerSession = $customerSession;
        $this->orderItemResource = $orderItemResource;
        $this->catalogConfig = $catalogConfig;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function isEnabled()
    {
        return $this->configuration->isLatestProductsPurchasedSliderEnabled();
    }

    public function getProducts()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return [];
        }
        if ($this->products === null) {
            $orderItems = $this->getCustomerLatestOrderItems();
            $this->products = $this->getSortedProductsFromOrderItems($orderItems);
        }
        return $this->products;
    }

    public function renderProductTile($product)
    {
        return $this->productTileRenderer->render($product, null, 'grid');
    }

    protected function getCustomerLatestOrderItems()
    {
        $connection = $this->orderItemResource->getConnection();
        $subQuery = $connection->select()
            ->from(['soi' => self::SALES_ORDER_ITEM_TABLE], 'MAX(soi.item_id)')
            ->join(['so' => self::SALES_ORDER_TABLE], 'soi.order_id = so.entity_id', '')
            ->where('soi.parent_item_id IS NULL')
            ->where('so.customer_id = ?', $this->customerSession->getCustomerId())
            ->where('so.status IN (?)', $this->configuration->getLatestProductPurchasedOrderStatus())
            ->group('product_id');
        $query = $connection->select()
            ->from(self::SALES_ORDER_ITEM_TABLE, ['product_id', 'product_options'])
            ->where('item_id IN(?)', new \Zend_Db_Expr($subQuery->__toString()))
            ->order('item_id DESC')
            ->limit($this->configuration->getLatestProductsPurchasedProductCount());
        return $connection->fetchAssoc($query);
    }

    protected function getSortedProductsFromOrderItems($orderItems)
    {
        $productIds = array_keys($orderItems);
        $productsCollection = $this->productCollectionFactory->create()
            ->addIdFilter($productIds)
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addPriceData()
            ->addTaxPercents()
            ->addStoreFilter()
            ->addUrlRewrite()
            ->addMediaGalleryData()
            ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
        return $this->sortProducts($productsCollection->getItems(), $productIds);
    }

    protected function sortProducts($products, $productIds)
    {
        $sortedProducts = [];
        foreach ($products as $product) {
            $index = array_search($product->getId(), $productIds);
            if ($index === false) {
                continue;
            }
            $sortedProducts[$index] = $product;
        }
        ksort($sortedProducts);
        return $sortedProducts;
    }
}
