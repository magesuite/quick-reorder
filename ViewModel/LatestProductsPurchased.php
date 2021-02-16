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
     * @var iterable|null
     */
    protected $products = null;

    public function __construct(
        \MageSuite\QuickReorder\Helper\Configuration $configuration,
        \Magento\Customer\Model\Session $customerSession,
        \MageSuite\QuickReorder\Model\ResourceModel\LatestOrderItemResourceInterface $latestOrderItemResource,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->configuration = $configuration;
        $this->customerSession = $customerSession;
        $this->latestOrderItemResource = $latestOrderItemResource;
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
