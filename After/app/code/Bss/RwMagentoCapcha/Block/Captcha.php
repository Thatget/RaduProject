<?php

namespace Bss\RwMagentoCapcha\Block;

use Magento\Framework\View\Element\Template;

class Captcha extends Template
{
    private $captcha;
    
    private $scopeConfig;
    
    public function __construct(
        Template\Context $context,
        \VladimirPopov\WebForms\Model\Captcha $captcha,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->captcha = $captcha;
        $this->scopeConfig = $scopeConfig;
        parent::__construct(
            $context,
            $data
        );
    }
    
    public function _toHtml()
    {
        $pubKey  = $this->scopeConfig->getValue('webforms/captcha/public_key');
        $privKey = $this->scopeConfig->getValue('webforms/captcha/private_key');
        if ($pubKey && $privKey) {
            $recaptcha = $this->captcha;
            $recaptcha->setPublicKey($pubKey);
            $recaptcha->setPrivateKey($privKey);
            $recaptcha->setTheme($this->scopeConfig->getValue('webforms/captcha/theme'));
            return $recaptcha->getHtml();
        }
        return false;
    }
}
