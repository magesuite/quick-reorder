<?php

namespace MageSuiteQuickReorder\CustomerData\ReorderBanner;

class ReorderBanner implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Sales\Helper\Reorder
     */
    protected $reorderHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Creativestyle\MageSuiteQuickReorder\Model\Customer\GetLastOrderByCustomerId
     */
    protected $getLastOrderByCustomerId;

    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Sales\Helper\Reorder $reorderHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Creativestyle\MageSuiteQuickReorder\Model\Customer\GetLastOrderByCustomerId $getLastOrderByCustomerId
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->priceCurrency = $priceCurrency;
        $this->customerSession = $customerSession;
        $this->reorderHelper = $reorderHelper;
        $this->getLastOrderByCustomerId = $getLastOrderByCustomerId;
    }

    /**
     * @inheritDoc
     */
    public function getSectionData()
    {
        if (!$this->reorderHelper->isAllowed() || !$this->customerSession->isLoggedIn()) {
            return [];
        }

        $customer = $this->customerSession->getCustomer();
        $lastOrder = $this->getLastOrderByCustomerId->execute($customer->getId());

        if (empty($lastOrder)) {
            return [];
        }

        return [
            'firstname' => $customer->getFirstname(),
            'lastOrderAmount' => $this->priceCurrency->convertAndFormat($lastOrder->getBaseGrandTotal(), false),
            'lastOrderItemsCount' => (int)$lastOrder->getTotalQtyOrdered(),
            'lastOrderReorderLink' => $this->urlBuilder->getUrl('sales/order/reorder', ['order_id' => $lastOrder->getId()]),
            'lastOrderViewLink' => $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $lastOrder->getId()])
        ];
    }
}