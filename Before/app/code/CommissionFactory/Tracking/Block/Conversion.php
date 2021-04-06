<?php

namespace CommissionFactory\Tracking\Block;

class Conversion extends \Magento\Framework\View\Element\Template
{
    protected $_advertiserId;

    protected $_orderIds;

    protected $_orderCollectionFactory;

    protected $_registry;

    /**
     * @param \Magento\Framework\View\Element\Template $context
     */
    public function __construct(
    	\Magento\Backend\Block\Template\Context $context,
    	\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $_orderCollectionFactory,
    	\Magento\Framework\Registry $_registry,
    	array $data = []
    ) {
    	$this->_orderCollectionFactory = $_orderCollectionFactory;
    	$this->_registry = $_registry;
        parent::__construct($context, $data);
    }

    protected function _toHtml()
	{
		if (empty($this->getAdvertiserId()) || empty($this->getOrderIds()) || !is_array($this->getOrderIds()))
		{
			return "";
		}

		$html = "";

		foreach ($this->getOrders($this->getOrderIds()) as $order)
		{
			$html .= "<script>\n";
			$html .= "    (function(a,b,c){a[b]=a[b]||function(){(a[b].q=a[b].q||[]).push(arguments);};a[c]=a[b];})(window,\"CommissionFactory\",\"cf\");\n";
			$html .= "\n";
			$html .= "    cf(\"set\", \"order\", " . json_encode($order->getIncrementId()) . ");\n";
			$html .= "    cf(\"set\", \"amount\", " . json_encode($order->getSubtotal() + $order->getDiscountAmount()) . ");\n";
			$html .= "    cf(\"set\", \"currency\", " . json_encode($order->getOrderCurrencyCode()) . ");\n";
			$html .= "    cf(\"set\", \"coupon\", " . json_encode($order->getCouponCode()) . ");\n";
			$html .= "\n";

			foreach ($order->getAllVisibleItems() as $item)
			{
				$html .= "    cf(\"add\", \"items\", { \"sku\": " . json_encode($item->getSku()) . ", \"price\": " . json_encode($item->getPrice()) . ", \"quantity\": " . json_encode($item->getQtyOrdered()) . " });\n";
			}

			$html .= "\n";
			$html .= "    cf(\"track\");\n";
			$html .= "</script>\n";
		}

		return $html;
	}

	public function getAdvertiserId()
	{
		return $this->_registry->registry('checkoutAdvertiserId');
	}

	public function getOrderIds()
	{
		return $this->_registry->registry('orderIds');
	}

	public function setAdvertiserId($advertiserId)
	{
		$this->_registry->register('checkoutAdvertiserId', $advertiserId);
	}

	public function setOrderIds($orderIds)
	{
		$this->_registry->register('orderIds', $orderIds);
	}

	public function getOrders($orderIds)
	{
		return $this->_orderCollectionFactory->create()->addFieldToSelect('*')->addFieldToFilter('entity_id', ['in' => $orderIds]);
	}
}