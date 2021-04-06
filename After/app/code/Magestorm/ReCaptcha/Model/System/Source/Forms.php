<?php
/**
 * Created by: Magestorm Team
 * Date: 2/4/2017
 * Time: 10:35
 */
namespace Magestorm\ReCaptcha\Model\System\Source;

class Forms extends \Magento\Framework\App\Config\Value
{
	public function toOptionArray()
	{
		$optionArray = [];
		$backendConfig = $this->_config->getValue("magestorm_recaptcha/setting/forms_area", 'default');
		if ($backendConfig) {
			foreach ($backendConfig as $formName => $formConfig) {
				if (!empty($formConfig['label'])) {
					$optionArray[] = ['label' => $formConfig['label'], 'value' => $formName];
				}
			}
		}
		return $optionArray;
	}
}