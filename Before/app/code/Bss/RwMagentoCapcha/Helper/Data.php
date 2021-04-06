<?php

namespace Bss\RwMagentoCapcha\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
class Data extends AbstractHelper
{

	protected $config;
	protected $scopeConfig;
    public function __construct(
    	\MSP\ReCaptcha\Model\Config $config,
    	\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Helper\Context $context
    ) {
    	$this->config = $config;
    	$this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    public function getConfig($value= "")
    {
    	return $this->scopeConfig->getValue('msp_securitysuite_recaptcha/frontend/'.$value);
    }

	public function isEnableProductEnquiry()
	{
		return $this->config->isEnabledFrontend() && (bool) $this->getConfig('enabled_enquity_form');
	}

	public function isEnableNewsLetter()
	{
		return $this->config->isEnabledFrontend() && (bool) $this->getConfig('enabled_news_letter');
	}
}