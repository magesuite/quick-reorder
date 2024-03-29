<?php
namespace MageSuite\QuickReorder\Helper;

class Configuration extends \Magento\Framework\App\Helper\AbstractHelper
{
    const LATEST_PRODUCTS_PURCHASED_SLIDER_ENABLED_PATH = 'quick_reorder/latest_products_purchased/enable_latest_products_purchased_slider';
    const LATEST_PRODUCTS_PURCHASED_ORDER_STATUS = 'quick_reorder/latest_products_purchased/order_status';
    const LATEST_PRODUCTS_PURCHASED_PRODUCT_COUNT = 'quick_reorder/latest_products_purchased/product_count';
    const REORDER_BANNER_ENABLED_PATH = 'quick_reorder/reorder_banner/enable_reorder_banner';
    const REORDER_BANNER_HIDE_TIME = 'quick_reorder/reorder_banner/reorder_banner_hide_time';

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

    public function getLatestProductPurchasedOrderStatus()
    {
        return explode(',', $this->scopeConfig->getValue(self::LATEST_PRODUCTS_PURCHASED_ORDER_STATUS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    public function getLatestProductsPurchasedProductCount()
    {
        return (int)$this->scopeConfig->getValue(self::LATEST_PRODUCTS_PURCHASED_PRODUCT_COUNT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function isReorderBannerEnabled()
    {
        return (bool)$this->scopeConfig->getValue(self::REORDER_BANNER_ENABLED_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getReorderBannerHideTime()
    {
        return (int)$this->scopeConfig->getValue(self::REORDER_BANNER_HIDE_TIME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
