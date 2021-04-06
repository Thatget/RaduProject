<?php

namespace Bss\WebShopAppsMatrixRate\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class CompositeConfigProvider implements ConfigProviderInterface
{
	private $scopeConfig;

	public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

	public function getConfig()
    {
    	$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        $output = [];
        if ($this->scopeConfig->getValue('carriers/matrixrate/minimum_select', $storeScope)) {
        	$output['bss_webshopapps_matrixrate'] = (float) $this->scopeConfig->getValue(
        		'carriers/matrixrate/minimum_input',
        		$storeScope
        	);
        }
        return $output;
    }
}
