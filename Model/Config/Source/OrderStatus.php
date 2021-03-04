<?php
namespace MageSuite\QuickReorder\Model\Config\Source;

class OrderStatus implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    public function __construct(\Magento\Sales\Model\Order\Config $orderConfig)
    {
        $this->orderConfig = $orderConfig;
    }

    public function toOptionArray()
    {
        $options = [];
        foreach ($this->orderConfig->getStatuses() as $code => $label) {
            $options[] = ['value' => $code, 'label' => $label];
        }
        return $options;
    }
}
