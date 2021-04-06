<?php
/**
 * Created by: Magestorm Team
 * Date: 2/4/2017
 * Time: 10:35
 */
namespace Magestorm\ReCaptcha\Model\System\Source;

class CaptchaType extends \Magento\Framework\App\Config\Value
{
	public function toOptionArray()
	{
		return [
			['value' => 'default', 'label' => __('reCAPTCHA V2')],
			['value' => 'invisible', 'label' => __('Invisible reCAPTCHA')]
		];
	}
}