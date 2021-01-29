<?php
namespace MageSuite\QuickReorder\Helper;

class Configuration extends \Magento\Framework\App\Helper\AbstractHelper
{
    const LATEST_PRODUCTS_PURCHASED_SLIDER_ENABLED_PATH = 'quick_reorder/latest_products_purchased/enable_latest_products_purchased_slider';
    const LATEST_PRODUCTS_PURCHASED_PRODUCT_COUNT = 'quick_reorder/latest_products_purchased/product_count';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface)
    {
        $this->scopeConfig = $scopeConfigInterface;
    }

    public function isLatestProductsPurchasedSliderEnabled()
    {
        return (bool)$this->scopeConfig->getValue(self::LATEST_PRODUCTS_PURCHASED_SLIDER_ENABLED_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getLatestProductsPurchasedProductCount()
    {
        return (int)$this->scopeConfig->getValue(self::LATEST_PRODUCTS_PURCHASED_PRODUCT_COUNT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
