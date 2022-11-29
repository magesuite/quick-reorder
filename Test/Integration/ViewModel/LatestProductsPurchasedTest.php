<?php
namespace MageSuite\QuickReorder\Test\Integration\ViewModel;

class LatestProductsPurchasedTest extends \PHPUnit\Framework\TestCase
{
    protected ?\Magento\TestFramework\ObjectManager $objectManager;

    protected ?\Magento\Customer\Model\Session $customerSession;

    protected ?\Magento\Customer\Model\Customer $customer;

    protected ?\Magento\Framework\App\ResourceConnection $connection;

    protected ?\MageSuite\QuickReorder\ViewModel\LatestProductsPurchasedInterface $latestProductsPurchased;

    public function setUp(): void
    {
        parent::setUp();

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->customerSession = $this->objectManager->get(\Magento\Customer\Model\Session::class);
        $this->customer = $this->objectManager->get(\Magento\Customer\Model\Customer::class);
        $this->connection = $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $this->latestProductsPurchased = $this->objectManager->get(\MageSuite\QuickReorder\ViewModel\LatestProductsPurchasedInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Sales/_files/orders_with_customer.php
     */
    public function testItReturnsProductsForLoggedInUserWhichHaveMultipleOrdersWithSameProduct()
    {
        $this->reindexPrices();

        $this->customerSession->setCustomerAsLoggedIn($this->getCustomer());
        $products = $this->latestProductsPurchased->getProducts();
        $this->assertCount(1, $products);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Sales/_files/order_with_customer_and_multiple_order_items.php
     */
    public function testItReturnsProductsForLoggedInUserWhichHaveOrderWithMultipleProducts()
    {
        $this->reindexPrices();

        $this->customerSession->setCustomerAsLoggedIn($this->getCustomer());
        $products = $this->latestProductsPurchased->getProducts();
        $this->assertCount(3, $products);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Sales/_files/order_with_customer_and_multiple_order_items.php
     * @magentoConfigFixture current_store quick_reorder/latest_products_purchased/product_count 2
     */
    public function testItProperlyLimitsProducts()
    {
        $this->reindexPrices();

        $this->customerSession->setCustomerAsLoggedIn($this->getCustomer());
        $products = $this->latestProductsPurchased->getProducts();
        $this->assertCount(2, $products);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Sales/_files/order_with_customer_and_multiple_order_items.php
     * @magentoConfigFixture current_store quick_reorder/latest_products_purchased/order_status complete
     */
    public function testItProperlyFiltersProductsByOrderStatus()
    {
        $this->reindexPrices();

        $this->customerSession->setCustomerAsLoggedIn($this->getCustomer());
        $products = $this->latestProductsPurchased->getProducts();
        $this->assertCount(0, $products);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testItDoesNotReturnProductsForGuest()
    {
        $this->reindexPrices();

        $this->customerSession->logout();
        $products = $this->latestProductsPurchased->getProducts();
        $this->assertCount(0, $products);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testItReturnsEmptyListForNewCustomer()
    {
        $this->reindexPrices();

        $this->customerSession->setCustomerAsLoggedIn($this->getCustomer());
        $products = $this->latestProductsPurchased->getProducts();
        $this->assertCount(0, $products);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture MageSuite_QuickReorder::Test/Integration/_files/order_with_customer_and_grouped_product.php
     */
    public function testItReturnsCorrectProductsForLoggedInUserWhichHaveOrderWithGroupedProducts()
    {
        $this->reindexPrices();

        $this->customerSession->setCustomerAsLoggedIn($this->getCustomer());
        $products = $this->latestProductsPurchased->getProducts();
        $this->assertCount(1, $products);

        $product = $products[0];
        $this->assertEquals(\Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE, $product->getTypeId());
    }

    protected function getCustomer()
    {
        return $this->customer->load(1);
    }

    protected function reindexPrices()
    {
        $connection = $this->connection->getConnection();
        $productIds = $connection->fetchCol(
            $connection->select()->from(['cpe' => $connection->getTableName('catalog_product_entity')], 'entity_id')
        );

        if (empty($productIds)) {
            return;
        }

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Framework\Indexer\IndexerRegistry::class);
        $indexerRegistry->get(\Magento\Catalog\Model\Indexer\Product\Price\Processor::INDEXER_ID)->reindexList($productIds);
    }
}
