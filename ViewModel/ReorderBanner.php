<?php
namespace MageSuite\QuickReorder\ViewModel;

class ReorderBanner implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \MageSuite\QuickReorder\Helper\Configuration
     */
    protected $configuration;

    public function __construct(\MageSuite\QuickReorder\Helper\Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getHideTime()
    {
        return $this->configuration->getReorderBannerHideTime();
    }
}
