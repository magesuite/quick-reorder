<?php

declare(strict_types=1);

namespace MageSuite\QuickReorder\CustomerData;

class ReorderBanner implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    protected \Magento\Framework\UrlInterface $urlBuilder;
    protected \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency;
    protected \Magento\Sales\Helper\Reorder $reorderHelper;
    protected \MageSuite\QuickReorder\Helper\Configuration $configuration;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \MageSuite\QuickReorder\Model\Customer\GetLastOrderByCustomerId $getLastOrderByCustomerId;

    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Sales\Helper\Reorder $reorderHelper,
        \MageSuite\QuickReorder\Helper\Configuration $configuration,
        \Magento\Customer\Model\Session $customerSession,
        \MageSuite\QuickReorder\Model\Customer\GetLastOrderByCustomerId $getLastOrderByCustomerId
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->priceCurrency = $priceCurrency;
        $this->customerSession = $customerSession;
        $this->reorderHelper = $reorderHelper;
        $this->configuration = $configuration;
        $this->getLastOrderByCustomerId = $getLastOrderByCustomerId;
    }

    /**
     * @inheritDoc
     */
    public function getSectionData()
    {
        if (!$this->reorderHelper->isAllowed()) {
            return [];
        }

        if (!$this->configuration->isReorderBannerEnabled()) {
            return [];
        }

        if (!$this->customerSession->isLoggedIn()) {
            return [];
        }

        $customer = $this->customerSession->getCustomer();
        $lastOrder = $this->getLastOrderByCustomerId->execute($customer->getId());

        if (empty($lastOrder)) {
            return [];
        }

        return [
            'firstname' => $customer->getFirstname(),
            'lastOrderAmount' => $this->priceCurrency->convertAndFormat($lastOrder->getGrandTotal(), false),
            'lastOrderItemsCount' => (int)$lastOrder->getTotalQtyOrdered(),
            'lastOrderItems' => $this->prepareOrderItems($lastOrder),
            'lastOrderReorderLink' => $this->urlBuilder->getUrl('sales/order/reorder', ['order_id' => $lastOrder->getId()]),
            'lastOrderViewLink' => $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $lastOrder->getId()])
        ];
    }

    public function prepareOrderItems(\Magento\Sales\Api\Data\OrderInterface $order): array
    {
        return array_values(
            array_filter(
                array_map(function ($orderItem) {
                    /** @var $orderItem \Magento\Sales\Api\Data\OrderItemInterface */
                    if ($orderItem->getParentItemId() !== null) {
                        return null;
                    }
                    return [
                        'name' => $orderItem->getName(),
                        'count' => (int)$orderItem->getQtyOrdered()
                    ];
                }, $order->getItems())
            )
        );
    }
}
