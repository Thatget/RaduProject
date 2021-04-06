<?php
/**
 * Created by: Magestorm Team
 * Date: 01/12/2017
 * Time: 16:55
 */

namespace Magestorm\ReCaptcha\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
	const FORMS_DEFAULT = [
		'user_login' => [
			'url' => 'customer/account/login',
			'form_selector' => '#login-form'
		],
		'user_create' => [
			'url' => 'customer/account/create',
			'form_selector' => '.form-create-account'
		],
		'user_forgotpassword' => [
			'url' => 'customer/account/forgotpassword',
			'form_selector' => '.forget'
		],
		'review_product' => [
			'url' => 'catalog/product/view',
			'form_selector' => '#review-form'
		],
		'email_friend' => [
			'url' => 'sendfriend/product/send',
			'form_selector' => '#product-sendtofriend-form'
		],
		'contact_us' => [
			'url' => 'contact',
			'form_selector' => '#contact-form'
		]
	];
	/*
 	 * @var $_jsonHelper \Magento\Framework\Json\Helper\Data
	 */
	protected $_jsonHelper;

	/*
 	 * @var array
	 */
	protected $_customForms;


	/**
	 * @var SerializerInterface
	 */
	private $serializer;

	/**
	 * Data constructor.
	 * @param JsonHelper $jsonHelper
	 * @param SerializerInterface $serializer
	 * @param Context $context
	 */
	public function __construct(
		JsonHelper $jsonHelper,
		SerializerInterface $serializer,
		Context $context
	) {
		$this->_jsonHelper = $jsonHelper;
		$this->serializer = $serializer;
		parent::__construct($context);
	}

	/**
	 * @return bool
	 */
	public function isEnabled() {
		return (bool)$this->_getConfigValue("magestorm_recaptcha/setting/is_active");
	}

	/**
	 * @param $value
	 * @return mixed
	 */
	protected function _getConfigValue($value) {
		return $this->scopeConfig->getValue($value);
	}

	/**
	 * @return mixed
	 */
	public function getCaptchaType() {
		return $this->_getConfigValue("magestorm_recaptcha/setting/captcha_type");
	}

	/**
	 * @return string
	 */
	public function getTheme() {
		return "light";
	}

	/**
	 * @return mixed
	 */
	public function getSiteKey() {
		if ($this->getCaptchaType() == 'default') {
			return $this->_getConfigValue("magestorm_recaptcha/setting/site_key");
		} else {
			return $this->_getConfigValue("magestorm_recaptcha/setting/invisible_site_key");
		}
	}

	/**
	 * @return mixed
	 */
	public function getSecretKey() {
		if ($this->getCaptchaType() == 'default') {
			return $this->_getConfigValue("magestorm_recaptcha/setting/secret_key");
		} else {
			return $this->_getConfigValue("magestorm_recaptcha/setting/invisible_secret_key");
		}
	}

	/**
	 * @return array
	 */
	public function getFormApplied(){
		$forms = explode(",", $this->_getConfigValue("magestorm_recaptcha/setting/forms"));
		return $forms;
	}

	/**
	 * @return array
	 */
	public function getCustomFormsApplied() {
		if (is_null($this->_customForms)) {
			$result = [];
			$customForms = $this->scopeConfig->getValue('magestorm_recaptcha/setting/custom_forms', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if ($customForms) {
				$customForms = $this->serializer->unserialize($customForms);
				foreach ($customForms as $customForm) {
					$result[] = $customForm;
				}
			}

			$this->_customForms = $result;
		}
		return $this->_customForms;
	}

	/**
	 * @param $url
	 * @return array
	 */
	public function getUrlsApplied ($url) {
		if ($this->isEnabled()) {
			$formsDefault = self::FORMS_DEFAULT;
			$arrayForm = [];
			$i = 0;
			foreach ($this->getFormApplied() as $formId) {
				if (strpos($url, $formsDefault[$formId]['url']) !== false) {
					$arrayForm[$i]['form_selector'] = $formsDefault[$formId]['form_selector'];
					$arrayForm[$i]['button_selector'] = '';
					$arrayForm[$i]['ko_selector'] = '';
					$i++;
					break;
				}
			}
			foreach ($this->getCustomFormsApplied() as $form) {
				if(!$form['url']) {
					$arrayForm[$i]['form_selector'] = $form['form_selector'];
					$arrayForm[$i]['button_selector'] = $form['button_selector'];
					$arrayForm[$i]['ko_selector'] = $form['ko_selector'];
					$i++;
				}
				elseif (strpos($url, $form['url']) !== false) {
					$arrayForm[$i]['form_selector'] = $form['form_selector'];
					$arrayForm[$i]['button_selector'] = $form['button_selector'];
					$arrayForm[$i]['ko_selector'] = $form['ko_selector'];
					$i++;
				}
			}
			return $arrayForm;
		}
		return null;
	}
}