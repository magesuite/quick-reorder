<?php
namespace MageSuite\QuickReorder\Test\Integration\ViewModel;

class LatestProductsPurchasedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \MageSuite\QuickReorder\ViewModel\LatestProductsPurchasedInterface
     */
    protected $latestProductsPurchased;

    public function setUp(): void
    {
        parent::setUp();
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->customerSession = $this->objectManager->get(\Magento\Customer\Model\Session::class);
        $this->customer = $this->objectManager->get(\Magento\Customer\Model\Customer::class);
        $this->latestProductsPurchased = $this->objectManager->get(\MageSuite\QuickReorder\ViewModel\LatestProductsPurchasedInterface::class);
        $this->reindexPrices();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Sales/_files/orders_with_customer.php
     */
    public function testItReturnsProductsForLoggedInUserWhichHaveMultipleOrdersWithSameProduct()
    {
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
        $productsIds = $this->objectManager->create(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->getAllIds();

        if(empty($productsIds)) {
            return;
        }

        $priceIndexer = $this->objectManager->get(\Magento\Catalog\Model\Indexer\Product\Price::class);
        $priceIndexer->execute($productsIds);
    }
}
