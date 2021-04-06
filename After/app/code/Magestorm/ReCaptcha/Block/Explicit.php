<?php
/**
 * Created by: Magestorm Team
 * Date: 01/12/2017
 * Time: 16:44
 */
namespace Magestorm\ReCaptcha\Block;

class Explicit extends \Magento\Framework\View\Element\Template {

	protected $_languages = [
		'ar_DZ|ar_SA|ar_KW|ar_MA|ar_EG|az_AZ|' => 'ar',
		'bg_BG' => 'bg',
		'ca_ES' => 'ca',
		'zh_CN' => 'zh-CN',
		'zh_HK|zh_TW' => 'zh-TW',
		'hr_HR' => 'hr',
		'cs_CZ' => 'cs',
		'da_DK' => 'da',
		'nl_NL' => 'nl',
		'en_GB|en_AU|en_NZ|en_IE|cy_GB' => 'en-GB',
		'en_US|en_CA' => 'en',
		'fil_PH' => 'fil',
		'fi_FI' => 'fi',
		'fr_FR' => 'fr',
		'fr_CA' => 'fr-CA',
		'de_DE' => 'de',
		'de_AT)' => 'de-AT',
		'de_CH' => 'de-CH',
		'el_GR' => 'el',
		'he_IL' => 'iw',
		'hi_IN' => 'hi',
		'hu_HU' => 'hu',
		'gu_IN|id_ID' => 'id',
		'it_IT|it_CH' => 'it',
		'ja_JP' => 'ja',
		'ko_KR' => 'ko',
		'lv_LV' => 'lv',
		'lt_LT' => 'lt',
		'nb_NO' => 'no',
		'fa_IR' => 'fa',
		'pl_PL' => 'pl',
		'pt_BR' => 'pt-BR',
		'pt_PT' => 'pt-PT',
		'ro_RO' => 'ro',
		'ru_RU' => 'ru',
		'sr_RS' => 'sr',
		'sk_SK' => 'sk',
		'sl_SI' => 'sl',
		'es_ES|gl_ES' => 'es',
		'es_AR|es_CL|es_CO|es_CR|es_MX|es_PA|es_PE|es_VE' => 'es-419',
		'sv_SE' => 'sv',
		'th_TH' => 'th',
		'tr_TR' => 'tr',
		'uk_UA' => 'uk',
		'vi_VN' => 'vi'
	];

	/**
	 * @var $_ReCaptchaHelper \Magestorm\ReCaptcha\Helper\Data
	 */
	protected $_ReCaptchaHelper;

	/**
	 * @var array
	 */
	protected $_formsApplied;

	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magestorm\ReCaptcha\Helper\Data $ReCaptchaHelper,
		array $data
	) {
		$this->_ReCaptchaHelper = $ReCaptchaHelper;
		parent::__construct($context, $data);
	}

	/**
	 * Get the reCaptcha theme setting.
	 *
	 * @return string
	 */
	public function getTheme()
	{
		return $this->_ReCaptchaHelper->getTheme();
	}

	/**
	 * Get the reCaptcha site key.
	 *
	 * @return string
	 */
	public function getSiteKey()
	{
		return $this->_ReCaptchaHelper->getSiteKey();
	}

	/**
	 * Get the reCaptcha secret key.
	 *
	 * @return string
	 */
	public function getSecretKey()
	{
		return $this->_ReCaptchaHelper->getSecretKey();
	}

	/**
	 * Get the reCaptcha type.
	 *
	 * @return string
	 */
	public function getCaptchaType()
	{
		return $this->_ReCaptchaHelper->getCaptchaType();
	}

	/**
	 * Get the reCaptcha forms selectors
	 *
	 * @return string
	 */
	public function getFormsApplied()
	{
		if (!isset($this->_formsApplied)) {
			$this->_formsApplied = $this->_ReCaptchaHelper->getUrlsApplied($this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => false]));
		}
		return $this->_formsApplied;
	}

	/**
	 * Get the reCaptcha forms selectors in json
	 *
	 * @return string
	 */
	public function getCaptchaSelectorsJson()
	{
		return \Zend_Json::encode($this->getFormsApplied() ? : []);
	}
}