<?php
namespace MageSuite\QuickReorder\ViewModel;

class LatestProductsPurchased implements \MageSuite\QuickReorder\ViewModel\LatestProductsPurchasedInterface
{
    /**
     * @var \MageSuite\QuickReorder\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \MageSuite\QuickReorder\Model\ResourceModel\LatestOrderItemResourceInterface
     */
    protected $latestOrderItemResource;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $catalogConfig;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * @var iterable|null
     */
    protected $products = null;

    public function __construct(
        \MageSuite\QuickReorder\Helper\Configuration $configuration,
        \Magento\Customer\Model\Session $customerSession,
        \MageSuite\QuickReorder\Model\ResourceModel\LatestOrderItemResourceInterface $latestOrderItemResource,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer
    ) {
        $this->configuration = $configuration;
        $this->customerSession = $customerSession;
        $this->latestOrderItemResource = $latestOrderItemResource;
        $this->catalogConfig = $catalogConfig;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->serializer = $serializer;
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

    protected function getCustomerLatestOrderItems()
    {
        return $this->latestOrderItemResource->getCustomerLatestOrderItems(
            $this->customerSession->getCustomerId(),
            $this->configuration->getLatestProductPurchasedOrderStatus(),
            $this->configuration->getLatestProductsPurchasedProductCount()
        );
    }

    protected function getSortedProductsFromOrderItems($orderItems)
    {
        $productIds = $this->getProductIdsFromOrderItems($orderItems);
        $productsCollection = $this->productCollectionFactory->create()
            ->addIdFilter($productIds)
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addPriceData()
            ->addTaxPercents()
            ->addStoreFilter()
            ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
            ->addUrlRewrite()
            ->addMediaGalleryData();

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

    protected function getProductIdsFromOrderItems($orderItems)
    {
        $productIds = [];

        foreach ($orderItems as $productId => $orderItem) {
            $productId = $this->resolveProductId($productId, $orderItem);

            if (!$productId) {
                continue;
            }

            $productIds[] = $productId;
        }

        return $productIds;
    }

    private function resolveProductId($productId, $orderItem)
    {
        $serializedProductOptions = $orderItem['product_options'] ?? null;

        if ($serializedProductOptions === null) {
            return $productId;
        }

        $productOptions = $this->serializer->unserialize($serializedProductOptions);
        $productType = $productOptions['super_product_config']['product_type'] ?? null;

        if ($productType == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            return $productOptions['super_product_config']['product_id'];
        }

        return $productId;
    }
}
