<?php

namespace CommissionFactory\Tracking\Observer;

class MultishippingCheckout implements \Magento\Framework\Event\ObserverInterface
{
	/**
     * @var \CommissionFactory\Tracking\Block\Conversion
     */
	protected $conversion;

	public function __construct(
		\CommissionFactory\Tracking\Block\Conversion $conversion
	) {
		$this->conversion = $conversion;
	}

	public function execute(\Magento\Framework\Event\Observer $observer) {
		$orderIds = $observer->getData('order_ids');
		if (empty($orderIds) || !is_array($orderIds))
		{
			return;
		}

		$this->conversion->setOrderIds($orderIds);
	}

}