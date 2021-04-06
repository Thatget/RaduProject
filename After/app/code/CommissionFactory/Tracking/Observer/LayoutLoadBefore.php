<?php

namespace CommissionFactory\Tracking\Observer;

class LayoutLoadBefore implements \Magento\Framework\Event\ObserverInterface
{

	/**
     * @var \CommissionFactory\Tracking\Block\Container
     */
	protected $container;

	/**
     * @var \CommissionFactory\Tracking\Block\Conversion
     */
	protected $conversion;

	/**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
	protected $scopeConfig;

	public function __construct(
		\CommissionFactory\Tracking\Block\Container $container,
		\CommissionFactory\Tracking\Block\Conversion $conversion,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	) {
		$this->container = $container;
		$this->conversion = $conversion;
		$this->scopeConfig = $scopeConfig;
	}

	public function execute(\Magento\Framework\Event\Observer $observer) {
		$advertiser = $this->scopeConfig->getValue('commissionfactory/tracking/advertiser', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if (empty($advertiser))
		{
			return;
		}

		$this->container->setAdvertiserId($advertiser);
		$this->conversion->setAdvertiserId($advertiser);

		$layout = $observer->getLayout();
		$layout->getUpdate()->addUpdate('<referenceBlock name="head.additional"><block name="commissionfactory_tracking_container" class="CommissionFactory\Tracking\Block\Container" /></referenceBlock>');
		$layout->getUpdate()->addUpdate('<referenceContainer  name="before.body.end"><block name="commissionfactory_tracking_conversion" class="CommissionFactory\Tracking\Block\Conversion" /></referenceContainer>');
	}

}