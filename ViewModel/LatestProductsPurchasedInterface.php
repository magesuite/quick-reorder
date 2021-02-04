<?php
namespace MageSuite\QuickReorder\ViewModel;

interface LatestProductsPurchasedInterface extends \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @return iterable
     */
    public function getProducts();
}
