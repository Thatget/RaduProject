<?php
/**
 * MageSpecialist
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magespecialist.it so we can send you a copy immediately.
 *
 * @category   MSP
 * @package    MSP_ReCaptcha
 * @copyright  Copyright (c) 2017 Skeeller srl (http://www.magespecialist.it)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bss\RwMagentoCapcha\Plugin\MspReCaptcha;

use Magento\Framework\App\Config\ScopeConfigInterface;

class LayoutSettings
{
	/**
     * @var Config
     */
    protected $config;

    /**
     * LayoutSettings constructor.
     * @param Config $config
     */

    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Return captcha config for frontend
     * @return array
     */
    public function afterGetCaptchaSettings(\MSP\ReCaptcha\Model\LayoutSettings $subject, $result)
    {
    	$result['enabled']['enquity_form'] = (bool) $this->scopeConfig
    		->getValue('msp_securitysuite_recaptcha/frontend/enabled_enquity_form');

    	$result['enabled']['news_letter'] = (bool) $this->scopeConfig
    		->getValue('msp_securitysuite_recaptcha/frontend/enabled_news_letter');
        return $result;
    }
}
