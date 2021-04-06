<?php
/**
 * @author      Vladimir Popov
 * @copyright   Copyright © 2019 Vladimir Popov. All rights reserved.
 */

namespace VladimirPopov\WebForms\Model;

use Magento\Framework\DataObject\IdentityInterface;

class Form extends AbstractModel implements IdentityInterface
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    /**
     * Form cache tag
     */
    const CACHE_TAG = 'webforms_form';

    /**
     * @var string
     */
    protected $_cacheTag = 'webforms_form';

    protected $_scopeConfig;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'webforms_form';

    protected $_emailTemplateCollection;

    protected $_session;

    protected $_fields_to_fieldsets = [];

    protected $_hidden = [];

    protected $_logic_target = [];

    protected $_fieldFactory;

    protected $_fieldsetFactory;

    protected $_request;

    protected $_formFactory;

    protected $_captcha;

    protected $fileCollectionFactory;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('VladimirPopov\WebForms\Model\ResourceModel\Form');
    }

    /**
     * Prepare form's statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    protected $_fieldsetCollectionFactory;

    protected $_fieldCollectionFactory;

    protected $_logicFactory;

    protected $_resultFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    protected $_subscriberFactory;

    protected $_storeManager;

    protected $_localDate;

    protected $formKey;

    protected $_uploaderFactory;

    protected $_storeCollectionFactory;

    protected $_webformsHelper;

    protected $dropzoneFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \VladimirPopov\WebForms\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory,
        \VladimirPopov\WebForms\Model\ResourceModel\Fieldset\CollectionFactory $fieldsetCollectionFactory,
        \VladimirPopov\WebForms\Model\ResourceModel\Field\CollectionFactory $fieldCollectionFactory,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $emailTemplateCollection,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        \VladimirPopov\WebForms\Model\FieldFactory $fieldFactory,
        \VladimirPopov\WebForms\Model\FieldsetFactory $fieldsetFactory,
        \VladimirPopov\WebForms\Model\LogicFactory $logicFactory,
        \VladimirPopov\WebForms\Model\StoreFactory $storeFactory,
        \VladimirPopov\WebForms\Model\ResourceModel\File\CollectionFactory $fileCollectionFactory,
        \Magento\Framework\App\RequestInterface $request,
        \VladimirPopov\WebForms\Model\ResultFactory $resultFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \VladimirPopov\WebForms\Model\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $localeDate,
        \VladimirPopov\WebForms\Model\Captcha $captcha,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \VladimirPopov\WebForms\Helper\Data $webformsHelper,
        \VladimirPopov\WebForms\Model\DropzoneFactory $dropzoneFactory,
        \VladimirPopov\WebForms\Model\ResourceModel\Form $resource = null,
        \VladimirPopov\WebForms\Model\ResourceModel\Form\Collection $resourceCollection = null,
        array $data = []
    )
    {
        $this->_storeCollectionFactory    = $storeCollectionFactory;
        $this->_fieldsetCollectionFactory = $fieldsetCollectionFactory;
        $this->_fieldCollectionFactory    = $fieldCollectionFactory;
        $this->_emailTemplateCollection   = $emailTemplateCollection;
        $this->_session                   = $sessionFactory->create();
        $this->_scopeConfig               = $scopeConfig;
        $this->_fieldFactory              = $fieldFactory;
        $this->_fieldsetFactory           = $fieldsetFactory;
        $this->_logicFactory              = $logicFactory;
        $this->_request                   = $request;
        $this->_resultFactory             = $resultFactory;
        $this->messageManager             = $messageManager;
        $this->_subscriberFactory         = $subscriberFactory;
        $this->_storeManager              = $storeManager;
        $this->_formFactory               = $formFactory;
        $this->_localDate                 = $localeDate;
        $this->_captcha                   = $captcha;
        $this->formKey                    = $formKey;
        $this->_uploaderFactory           = $uploaderFactory;
        $this->fileCollectionFactory      = $fileCollectionFactory;
        $this->_webformsHelper            = $webformsHelper;
        $this->dropzoneFactory            = $dropzoneFactory;
        parent::__construct($storeFactory, $context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Is active
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->getData('is_active');
    }

    public function getFieldByCode($fieldCode)
    {
        $field = $this->_fieldFactory->create()
            ->getCollection()
            ->addFilter('webform_id', $this->getId())
            ->addFilter('code', $fieldCode)
            ->getFirstItem();
        return $field;
    }

    public function getTemplateOptions()
    {
        $default   = [0 => __('Default')];
        $templates = $this->_emailTemplateCollection->create()->toOptionArray();
        return array_merge($default, $templates);
    }

    public function getFieldsetsOptionsArray()
    {

        $collection = $this->_fieldsetFactory->create()
            ->setStoreId($this->getStoreId())
            ->getCollection()
            ->addFilter('webform_id', $this->getId())
            ->setOrder('position', 'asc');
        $options    = [0 => '...'];
        foreach ($collection as $o) {
            $options[$o->getId()] = $o->getName();
        }
        return $options;
    }

    public function canAccess()
    {
        if ($this->_webformsHelper->isAllowed($this->getId()))
            return true;

        if ($this->getAccessEnable()) {
            $groupId = $this->_session->getCustomerGroupId();

            if (in_array($groupId, $this->getAccessGroups()))
                return true;
            return false;
        }
        return true;
    }

    public function _getFieldsToFieldsets()
    {
        return $this->_fields_to_fieldsets;
    }

    public function _setLogicTarget($logic_target)
    {
        $this->_logic_target = $logic_target;
        return $this;
    }

    public function _getLogicTarget($uid = false)
    {
        $logic_target = $this->_logic_target;
        // apply unique id
        if ($uid) {
            $logic_target = [];
            foreach ($this->_logic_target as $target) {
                if (strstr($target['id'], 'field_')) $target['id'] = str_replace('field_', 'field_' . $uid, $target['id']);
                if (strstr($target['id'], 'fieldset_')) $target['id'] = str_replace('fieldset_', 'fieldset_' . $uid, $target['id']);
                if (strstr($target['id'], 'submit')) $target['id'] = str_replace('submit', 'submit' . $uid, $target['id']);
                $logic_target[] = $target;
            }
        }
        return $logic_target;
    }

    public function _setFieldsToFieldsets($fields_to_fieldsets)
    {
        $this->_fields_to_fieldsets = $fields_to_fieldsets;
        return $this;
    }

    public function _getHidden()
    {
        return $this->_hidden;
    }

    public function _setHidden($hidden)
    {
        $this->_hidden = $hidden;
        return $this;
    }

    public function getEmailSettings()
    {
        $settings["email_enable"] = $this->getSendEmail();
        $settings["email"]        = $this->_scopeConfig->getValue('webforms/email/email');
        if ($this->getEmail())
            $settings["email"] = $this->getEmail();
        return $settings;
    }

    public function getFieldsToFieldsets($all = false, \VladimirPopov\WebForms\Model\Result $result = null)
    {
        if ($this->_fields_to_fieldsets) return $this->_fields_to_fieldsets;


        $logic_rules = $this->getLogic(true);

        //get form fieldsets
        $fieldsets = $this->_fieldsetFactory->create()
            ->getCollection()
            ->setStoreId($this->getStoreId())
            ->addFilter('webform_id', $this->getId());

        if (!$all)
            $fieldsets->addFilter('is_active', self::STATUS_ENABLED);

        $fieldsets->getSelect()->order('position asc');

        //get form fields
        $fields = $this->_fieldFactory->create()
            ->getCollection()
            ->setStoreId($this->getStoreId())
            ->addFilter('webform_id', $this->getId());

        if (!$all) {
            $fields->addFilter('is_active', self::STATUS_ENABLED);
        }

        $fields->getSelect()->order('position asc');

        //fields to fieldsets
        //make zero fieldset
        $fields_to_fieldsets = array();
        $hidden              = array();
        $required_fields     = array();
        $default_data        = array();

        foreach ($fields as $field) {
            $field->setWebform($this);
            // set default data
            if (strstr($field->getType(), 'select')) {
                $options         = $field->getOptionsArray();
                $checked_options = array();
                foreach ($options as $o) {
                    if ($o['checked']) {
                        $checked_options[] = $o['value'];
                    }
                }
                if (count($checked_options)) {
                    $default_data[$field->getId()] = $checked_options;
                }
            }

            //set default visibility
            $field->setData('logic_visibility', \VladimirPopov\WebForms\Model\Logic::VISIBILITY_VISIBLE);

            if ($field->getFieldsetId() == 0) {
                if ($all || $field->getType() != 'hidden') {
                    if ($field->getRequired()) $required_fields[] = 'field_' . $field->getId();
                    if ($all || $field->getIsActive())
                        $fields_to_fieldsets[0]['fields'][] = $field;
                } elseif ($field->getType() == 'hidden') {
                    $hidden[] = $field;
                }
            }
        }


        foreach ($fieldsets as $fieldset) {
            foreach ($fields as $field) {
                if ($field->getFieldsetId() == $fieldset->getId()) {
                    if ($all || $field->getType() != 'hidden') {
                        if ($all || $field->getIsActive())
                            $fields_to_fieldsets[$fieldset->getId()]['fields'][] = $field;
                    } elseif ($field->getType() == 'hidden') {
                        if ($all || $field->getIsActive())
                            $hidden[] = $field;
                    }
                }
            }
            if (!empty($fields_to_fieldsets[$fieldset->getId()]['fields'])) {
                $fields_to_fieldsets[$fieldset->getId()]['name']           = $fieldset->getName();
                $fields_to_fieldsets[$fieldset->getId()]['result_display'] = $fieldset->getResultDisplay();
                $fields_to_fieldsets[$fieldset->getId()]['css_class']      = $fieldset->getCssClass() . " " . $fieldset->getResponsiveCss();
                $fields_to_fieldsets[$fieldset->getId()]['css_style']      = $fieldset->getCssStyle();
            }
        }

        // set logic attributes
        $logic_target   = array();
        $hidden_targets = array();
        $logicModel     = $this->_logicFactory->create();
        $target         = array();
        foreach ($fields_to_fieldsets as $fieldset_id => $fieldset) {
            $fields_to_fieldsets[$fieldset_id]['logic_visibility'] = \VladimirPopov\WebForms\Model\Logic::VISIBILITY_VISIBLE;
            if (count($logic_rules))
                foreach ($logic_rules as $logic) {
                    if ($logic->getAction() == \VladimirPopov\WebForms\Model\Logic\Action::ACTION_SHOW && $logic->getIsActive()) {

                        // check fieldset visibility
                        if (in_array('fieldset_' . $fieldset_id, $logic->getTarget())) {
                            $fields_to_fieldsets[$fieldset_id]['logic_visibility'] = \VladimirPopov\WebForms\Model\Logic::VISIBILITY_HIDDEN;
                        }

                        // check fields visibility
                        foreach ($fieldset['fields'] as $field) {
                            if (in_array('field_' . $field->getId(), $logic->getTarget())) {
                                $field->setData('logic_visibility', \VladimirPopov\WebForms\Model\Logic::VISIBILITY_HIDDEN);
                            }
                        }
                    }
                }
        }

        $field_map = array();
        foreach ($fields_to_fieldsets as $fieldset_id => $fieldset) {
            foreach ($fieldset["fields"] as $field) {
                $field_map['fieldset_' . $fieldset_id][] = $field->getId();
            }
        }

        // check field values and assign visibility
        if ($result && $result->getId()) {
            $result->addFieldArray();
            $default_data = $result->getData('field');
        }
        foreach ($fields_to_fieldsets as $fieldset_id => $fieldset) {
            $target['id']                                          = 'fieldset_' . $fieldset_id;
            $target['logic_visibility']                            = $fieldset['logic_visibility'];
            $visibility                                            = $logicModel->getTargetVisibility($target, $logic_rules, $default_data, $field_map);
            $fields_to_fieldsets[$fieldset_id]['logic_visibility'] = $visibility ?
                \VladimirPopov\WebForms\Model\Logic::VISIBILITY_VISIBLE :
                \VladimirPopov\WebForms\Model\Logic::VISIBILITY_HIDDEN;
            if (!$visibility) $hidden_targets[] = "fieldset_" . $fieldset_id;

            // check fields visibility
            foreach ($fieldset['fields'] as $field) {
                $target['id']               = 'field_' . $field->getId();
                $target['logic_visibility'] = $field->getData('logic_visibility');
                $visibility                 = $logicModel->getTargetVisibility($target, $logic_rules, $default_data, $field_map);
                $field->setData('logic_visibility', $visibility ?
                    \VladimirPopov\WebForms\Model\Logic::VISIBILITY_VISIBLE :
                    \VladimirPopov\WebForms\Model\Logic::VISIBILITY_HIDDEN);
                if (!$visibility) $hidden_targets[] = "field_" . $field->getId();
            }

        }

        // check submit button visibility
        $target['id']               = 'submit';
        $target['logic_visibility'] = true;
        $visibility                 = $logicModel->getTargetVisibility($target, $logic_rules, $default_data, $field_map);
        if (!$visibility) $hidden_targets[] = $target['id'];

        // set logic target
        foreach ($logic_rules as $logic)
            if ($logic->getIsActive())
                foreach ($logic->getTarget() as $target) {
                    $required = false;
                    if (in_array($target, $required_fields)) $required = true;
                    if (!in_array($target, $logic_target))
                        $logic_target[] = array(
                            "id" => $target,
                            "logic_visibility" =>
                                in_array($target, $hidden_targets) ?
                                    \VladimirPopov\WebForms\Model\Logic::VISIBILITY_HIDDEN :
                                    \VladimirPopov\WebForms\Model\Logic::VISIBILITY_VISIBLE,
                            "required" => $required
                        );
                }

        $this->_setLogicTarget($logic_target);
        $this->_setFieldsToFieldsets($fields_to_fieldsets);
        $this->_setHidden($hidden);
        $this->setHiddenTargets($hidden_targets);

        $this->_fields_to_fieldsets = $fields_to_fieldsets;
        return $fields_to_fieldsets;
    }

    public function getDashboardGroups()
    {
        if ($this->getData('dashboard_groups')) return $this->getData('dashboard_groups');
        return array();
    }

    public function getAccessGroups()
    {
        if ($this->getData('access_groups')) return $this->getData('access_groups');
        return array();
    }

    public function getLogic($active = false)
    {
        $collection = $this->_logicFactory->create()
            ->getCollection()
            ->setStoreId($this->getStoreId())
            ->addWebformFilter($this->getId());
        if ($active)
            $collection->addFilter('main_table.is_active', 1);

        return $collection;
    }

    public function getLogicTargetVisibility($target, $logic_rules, $data)
    {
        $logic     = $this->_logicFactory->create();
        $field_map = [];
        foreach ($this->_fields_to_fieldsets as $fieldset_id => $fieldset) {
            foreach ($fieldset["fields"] as $field) {
                $field_map['fieldset_' . $fieldset_id][] = $field->getId();
            }
        }
        if (empty($field_map['fieldset_0'])) $field_map['fieldset_0'] = [];
        $field_map['fieldset_0'][] = 'submit';
        return $logic->getTargetVisibility($target, $logic_rules, $data, $field_map);
    }

    public function getSubmitButtonText()
    {
        $submit_button_text = trim($this->getData('submit_button_text'));
        if (strlen($submit_button_text) == 0)
            $submit_button_text = 'Submit';
        return $submit_button_text;
    }

    public function captchaAvailable()
    {
        if ($this->_scopeConfig->getValue('webforms/captcha/public_key') && $this->_scopeConfig->getValue('webforms/captcha/private_key'))
            return true;
        return false;
    }

    public function useCaptcha()
    {
        $useCaptcha = true;
        if ($this->getCaptchaMode() != 'default') {
            $captcha_mode = $this->getCaptchaMode();
        } else {
            $captcha_mode = $this->_scopeConfig->getValue('webforms/captcha/mode');
        }
        if ($captcha_mode == "off" || !$this->captchaAvailable())
            $useCaptcha = false;
        if ($this->_session->getCustomerId() && $captcha_mode == "auto")
            $useCaptcha = false;
        if ($this->getData('disable_captcha'))
            $useCaptcha = false;

        return $useCaptcha;
    }

    public function getCaptcha()
    {
        $pubKey  = $this->_scopeConfig->getValue('webforms/captcha/public_key');
        $privKey = $this->_scopeConfig->getValue('webforms/captcha/private_key');

        $recaptcha = false;

        if ($pubKey && $privKey) {
            $recaptcha = $this->_captcha;
            $recaptcha->setPublicKey($pubKey);
            $recaptcha->setPrivateKey($privKey);
            $recaptcha->setTheme($this->_scopeConfig->getValue('webforms/captcha/theme'));
        }
        return $recaptcha;
    }

    public function validatePostResult()
    {
        $postData = $this->getPostData();
        if (empty($postData['field'])) $postData['field'] = [];

        $result_id = false;
        if (!empty($postData['result_id'])) $result_id = $postData['result_id'];

        if ($this->_registry->registry('webforms_errors_flag_' . $this->getId())) return $this->_registry->registry('webforms_errors_' . $this->getId());

        $errors = [];

        // check access settings
        if (!$this->canAccess()) {
            $errors[] = __('You don\'t have enough permissions to access this content. Please login.');
        }

        // check gdpr
        if (!$this->_webformsHelper->isAdmin())
            if ($this->getData('show_gdpr_agreement_checkbox') && $this->getData('gdpr_agreement_checkbox_required')) {
                if (empty($postData['gdpr'])) {
                    if ($this->getData('gdpr_agreement_checkbox_error_text'))
                        $errors[] = $this->getData('gdpr_agreement_checkbox_error_text');
                    else
                        $errors[] = __('Please confirm the agreement!');
                }
            }

        // check form key
        if ($this->_storeManager->getStore()->getConfig('webforms/general/formkey')) {
            if ($this->_request->getParam('form_key')) {
                if ($this->formKey->getFormKey() != $this->_request->getParam('form_key')) {
                    $errors[] = __('Invalid form key.');
                }
            } else {
                $errors[] = __('Form key is missing.');
            }
        }

        // check honeypot captcha
        if ($this->_scopeConfig->isSetFlag('webforms/honeypot/enable')) {
            if ($this->_request->getParam('message')) {
                $errors[] = __('Spam bot detected. Honeypot field should be empty.');
            }
        }

        // check custom validation
        $logic_rules                                       = $this->getLogic();
        $fields_to_fieldsets                               = $this->getFieldsToFieldsets();
        $fields_to_fieldsets['hidden']['fields']           = $this->_getHidden();
        $fields_to_fieldsets['hidden']['logic_visibility'] = true;
        foreach ($fields_to_fieldsets as $fieldset_id => $fieldset)
            foreach ($fieldset['fields'] as $field) {


                // check required
                $requiredFailed = false;
                $hint           = htmlspecialchars(trim($field->getHint()));
                if ($field->getRequired() && empty($postData["field"][$field->getId()]) && $field->getType() == 'hidden') {
                    $requiredFailed = true;
                    $errorMsg       = $field->getValidationAdvice() ? $field->getValidationAdvice() : __('%1 is required', $field->getName());
                    if (!in_array($errorMsg, $errors)) {
                    }
                    $errors[] = $errorMsg;
                }

                if ($field->getRequired() && is_array($postData["field"]) && $field->getType() != 'file' && $field->getType() != 'image') {

                    $dataMissing = true;
                    foreach ($postData["field"] as $key => $value) {
                        if (is_array($value)) {
                            $value = implode(" ", $value);
                        }
                        $value = trim(strval($value));
                        if ($key == $field->getId()) {
                            $dataMissing = false;
                        }
                        if (
                            $key == $field->getId()
                            &&
                            ($value == $hint || $value == '')
                        ) {
                            // check logic visibility
                            $target_field    = ["id" => 'field_' . $field->getId(), 'logic_visibility' => $field->getData('logic_visibility')];
                            $target_fieldset = ["id" => 'fieldset_' . $fieldset_id, 'logic_visibility' => $fieldset['logic_visibility']];

                            if (
                                $this->getLogicTargetVisibility($target_field, $logic_rules, $postData['field']) &&
                                $this->getLogicTargetVisibility($target_fieldset, $logic_rules, $postData)
                            ) {
                                $requiredFailed = true;
                                $errorMsg       = $field->getValidationAdvice() ? $field->getValidationAdvice() : __('%1 is required', $field->getName());
                                if (!in_array($errorMsg, $errors))
                                    $errors[] = $errorMsg;
                            }
                        }
                    }
                    // if field is required but is not in the post array
                    if ($dataMissing) {
                        // check logic visibility
                        $target_field    = ["id" => 'field_' . $field->getId(), 'logic_visibility' => $field->getData('logic_visibility')];
                        $target_fieldset = ["id" => 'fieldset_' . $fieldset_id, 'logic_visibility' => $fieldset['logic_visibility']];

                        if (
                            $this->getLogicTargetVisibility($target_field, $logic_rules, $postData['field']) &&
                            $this->getLogicTargetVisibility($target_fieldset, $logic_rules, $postData['field'])
                        ) {
                            $requiredFailed = true;
                            $errorMsg       = $field->getValidationAdvice() ? $field->getValidationAdvice() : __('%1 is required', $field->getName());
                            if (!in_array($errorMsg, $errors))
                                $errors[] = $errorMsg;
                        }
                    }

                }

                // checkbox minimum and maximum options
                if ($field->getType() == 'select/checkbox' && !empty($postData['field'][$field->getId()])) {
                    $count = count($postData['field'][$field->getId()]);
                    if ($count > 0 && $count < $field->getValue('options_checkbox_min')) {
                        $validate_message = __('Please check at least %1 options', $field->getValue('options_checkbox_min'));
                        if ($field->getValue('options_checkbox_min_error_text'))
                            $validate_message = $field->getValue('options_checkbox_min_error_text');
                        $errors[] = $validate_message;
                    }
                    if ($field->getValue('options_checkbox_max') > 0 && $count > $field->getValue('options_checkbox_max')) {
                        $validate_message = __('Please check not more than %1', $field->getValue('options_checkbox_max'));
                        if ($field->getValue('options_checkbox_max_error_text'))
                            $validate_message = $field->getValue('options_checkbox_max_error_text');
                        $errors[] = $validate_message;
                    }
                }

                // check custom validation
                if ($field->getIsActive() && $field->getValidateRegex() && $field->getRequired() && !$requiredFailed) {
                    // check logic visibility
                    $target_field    = ["id" => 'field_' . $field->getId(), 'logic_visibility' => $field->getData('logic_visibility')];
                    $target_fieldset = ["id" => 'fieldset_' . $fieldset_id, 'logic_visibility' => $fieldset['logic_visibility']];

                    if (
                        $this->getLogicTargetVisibility($target_field, $logic_rules, $postData['field']) &&
                        $this->getLogicTargetVisibility($target_fieldset, $logic_rules, $postData['field'])
                    ) {
                        $pattern = trim($field->getValidateRegex());

                        // clear global modifier
                        if (substr($pattern, 0, 1) == '/' && substr($pattern, -2) == '/g') $pattern = substr($pattern, 0, strlen($pattern) - 1);

                        $status = @preg_match($pattern, "Test");
                        if (false === $status) {
                            $pattern = "/" . $pattern . "/";
                        }
                        $validate = new \Zend_Validate_Regex($pattern);
                        foreach ($postData["field"] as $key => $value) {
                            if ($key == $field->getId() && !$validate->isValid($value)) {
                                $errors[] = $field->getName() . ": " . $field->getValidateMessage();
                            }
                        }
                    }
                }

                // check e-mail
                if ($field->getIsActive() && $field->getType() == 'email') {
                    if (!empty($postData['field'][$field->getId()])) {
                        $email_validate = new \Zend_Validate_EmailAddress;
                        if (!$email_validate->isValid($postData['field'][$field->getId()])) {
                            $errors[] = __('Invalid e-mail address specified.');
                        }
                        if ($this->_webformsHelper->isInEmailStoplist($postData['field'][$field->getId()])) {
                            $errors[] = __('E-mail address is blocked: %1', $postData['field'][$field->getId()]);
                        }
                    }
                }

                // validate unique
                if ($field->getIsActive() && $field->getValidateUnique()) {
                    if (!empty($postData['field'][$field->getId()])) {
                        $value      = $postData['field'][$field->getId()];
                        $collection = $this->_resultFactory->create()->getCollection();
                        if ($result_id) $collection->addFilter('main_table.id', $result_id);
                        $count = $collection->addFieldFilter($field->getId(), $value, true)->getSize();
                        if (($count && !$result_id) || ($count > 1 && $result_id)) {
                            $errors[] = $field->getValidateUniqueMessage() ? $field->getValidateUniqueMessage() : __('Duplicate value has been found: %1', $postData['field'][$field->getId()]);
                        }
                    }
                }
            }

        // check files
        $files = $this->getUploadedFiles();
        foreach ($files as $field_name => $file) {
            if (!empty($file['error']) && $file['error'] == UPLOAD_ERR_INI_SIZE) {
                $errors[] = __('Uploaded file %1 exceeds allowed limit: %2', $file['name'], ini_get('upload_max_filesize'));
            }
            if (isset($file['name']) && file_exists($file['tmp_name'])) {
                $field_id                     = str_replace('file_', '', $field_name);
                $postData['field'][$field_id] = \Magento\Framework\File\Uploader::getCorrectFileName($file['name']);
                $field                        = $this->_fieldFactory->create()
                    ->setStoreId($this->getStoreId())
                    ->load($field_id);
                $filesize                     = round($file['size'] / 1024);
                $images_upload_limit          = $this->_scopeConfig->getValue('webforms/images/upload_limit');
                if ($this->getImagesUploadLimit() > 0) {
                    $images_upload_limit = $this->getImagesUploadLimit();
                }
                $files_upload_limit = $this->_scopeConfig->getValue('webforms/files/upload_limit');
                if ($this->getFilesUploadLimit() > 0) {
                    $files_upload_limit = $this->getFilesUploadLimit();
                }
                if ($field->getType() == 'image') {
                    // check file size
                    if ($filesize > $images_upload_limit && $images_upload_limit > 0) {
                        $errors[] = __('Uploaded image %1 (%2 kB) exceeds allowed limit: %3 kB', $file['name'], $filesize, $images_upload_limit);
                    }

                    // check that file is valid image
                    if (!@getimagesize($file['tmp_name'])) {
                        $errors[] = __('Unsupported image compression: %1', $file['name']);
                    }

                } else {
                    // check file size
                    if ($filesize > $files_upload_limit && $files_upload_limit > 0) {
                        $errors[] = __('Uploaded file %1 (%2 kB) exceeds allowed limit: %3 kB', $file['name'], $filesize, $files_upload_limit);
                    }


                }
                $allowed_extensions = $field->getAllowedExtensions();
                // check for allowed extensions
                if (count($allowed_extensions)) {
                    preg_match('/\.([^\.]+)$/', $file['name'], $matches);
                    $file_ext = strtolower($matches[1]);
                    // check file extension
                    if (!in_array($file_ext, $allowed_extensions)) {
                        $errors[] = __('Uploaded file %1 has none of allowed extensions: %2', $file['name'], implode(', ', $allowed_extensions));
                    }
                }

                $restricted_extensions = $field->getRestrictedExtensions();
                // check for restricted extensions
                if (count($restricted_extensions)) {
                    preg_match('/\.([^\.]+)$/', $file['name'], $matches);
                    $file_ext = strtolower($matches[1]);
                    if (in_array($file_ext, $restricted_extensions)) {
                        $errors[] = __('Uploading of potentially dangerous files is not allowed.');
                    }

                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $type  = $finfo->file($file['tmp_name']);
                    if (strstr($type, 'php')) {
                        $errors[] = __('Uploading of potentially dangerous files is not allowed.');
                    }
                }
                // check for valid filename
                if ($this->_scopeConfig->getValue('webforms/files/validate_filename') && !preg_match('/^[a-zA-Z0-9_\s-\.]+$/', $file['name'])) {
                    $errors[] = __('Uploaded file %1 has non-latin characters in the name', $file['name']);
                }
            }
        }

        $validate = new \Magento\Framework\DataObject(['errors' => $errors]);

        $this->_eventManager->dispatch('webforms_validate_post_result', ['webform' => $this, 'validate' => $validate]);

        // check captcha
        if ($this->useCaptcha() && count($validate->getData('errors')) < 1) {
            $errors = $validate->getData('errors');
            if ($this->_request->getParam('g-recaptcha-response')) {
                $verify = $this->getCaptcha()->verify($this->_request->getPost('g-recaptcha-response'));
                if (!$verify) {
                    $errors[] = __('Verification code was not correct. Please try again.');
                }
            } else {
                $errors[] = __('Verification code was not correct. Please try again.');
            }
            $validate->setData('errors', $errors);
        }

        $this->_registry->register('webforms_errors_flag_' . $this->getId(), true);
        $this->_registry->register('webforms_errors_' . $this->getId(), $validate->getData('errors'));

        return $validate->getData('errors');
    }

    public function getRealIp()
    {
        $ip = false;

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);

            if ($ip) {
                array_unshift($ips, $ip);
                $ip = false;
            }

            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {
                    if (version_compare(phpversion(), "5.0.0", ">=")) {
                        if (ip2long($ips[$i]) != false) {
                            $ip = $ips[$i];
                            break;
                        }
                    } else {
                        if (ip2long($ips[$i]) != -1) {
                            $ip = $ips[$i];
                            break;
                        }
                    }
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    public function getPost($config)
    {
        $postData = $this->_request->getPostValue();
        if (!empty($config['prefix'])) {
            $postData = $this->_request->getParam($config['prefix']);
        }
        if (empty($postData['field'])) $postData['field'] = [];

        // check visibility
        $fields_to_fieldsets = $this->getFieldsToFieldsets();
        $logic_rules         = $this->getLogic(true);
        foreach ($fields_to_fieldsets as $fieldset) {
            foreach ($fieldset['fields'] as $field) {
                $target_field     = array("id" => 'field_' . $field->getId(), 'logic_visibility' => $field->getData('logic_visibility'));
                $field_visibility = $this->getLogicTargetVisibility($target_field, $logic_rules, $postData['field']);
                $field->setData('visible', $field_visibility);
                if (!$field_visibility) {
                    $postData['field'][$field->getId()] = '';
                }
            }
        }
        return $postData;
    }

    public function savePostResult($config = [])
    {
        try {
            $postData = $this->getPost($config);

            $result = $this->_resultFactory->create();

            $new_result = true;
            if (!empty($postData['result_id'])) {
                $new_result = false;
                $result->load($postData['result_id'])->addFieldArray(false, ['select/radio', 'select/checkbox']);

                foreach ($result->getData('field') as $key => $value) {
                    if (!array_key_exists($key, $postData['field'])) {
                        $postData['field'][$key] = '';
                    }
                }

            }

            if (empty($postData['field'])) $postData['field'] = [];

            $this->setData('post_data', $postData);

            $errors = $this->validatePostResult();

            if (count($errors)) {
                foreach ($errors as $error) {
                    $this->messageManager->addError($error);
                    if ($this->_scopeConfig->getValue('webforms/general/store_temp_submission_data'))
                        $this->_session->setData('webform_result_tmp_' . $this->getId(), $postData);
                }
                return false;
            }

            $this->_session->setData('webform_result_tmp_' . $this->getId(), false);

            $iplong = ip2long($this->getRealIp());

            $files = $this->getUploadedFiles();
            foreach ($files as $field_name => $file) {
                $field_id = str_replace('file_', '', $field_name);
                if ($file['name']) {
                    $postData['field'][$field_id] = $file['name'];
                }

            }

            // delete files

            foreach ($this->_getFieldsToFieldsets() as $fieldset) {
                foreach ($fieldset['fields'] as $field) {
                    if ($field->getType() == 'file' || $field->getType() == 'image') {
                        if (!empty($postData['delete_file_' . $field->getId()])) {
                            $resultFiles = $this->fileCollectionFactory->create()
                                ->addFilter('result_id', $result->getId())
                                ->addFilter('field_id', $field->getId());
                            foreach ($resultFiles as $resultFile) {
                                $resultFile->delete();
                            }
                            $postData['field'][$field->getId()] = '';
                        }
                    }
                }
            }

            if ($new_result) {
                $approve = 1;
                if ($this->getApprove()) $approve = 0;
            }

            $result->setData('field', $postData['field'])
                ->setWebformId($this->getId())
                ->setStoreId($this->_storeManager->getStore()->getId())
                ->setCustomerId($this->_session->getCustomerId());
            if ($this->_scopeConfig->getValue('webforms/gdpr/collect_customer_ip')) {
                $result->setCustomerIp($iplong);
            }
            if (!empty($approve))
                $result->setApproved($approve);
            $result->setWebform($this);
            $result->save();

            // upload files
            $result->getUploader()->upload();

            $this->_eventManager->dispatch('webforms_result_submit', ['result' => $result, 'webform' => $this]);

            // send e-mail

            if ($new_result) {

                $emailSettings = $this->getEmailSettings();

                // send admin notification
                if ($emailSettings['email_enable']) {
                    $result->sendEmail();
                }

                // send customer notification
                if ($this->getDuplicateEmail()) {
                    $result->sendEmail('customer');
                }

                // email contact
                $logic_rules         = $this->getLogic();
                $fields_to_fieldsets = $this->_getFieldsToFieldsets();
                foreach ($fields_to_fieldsets as $fieldset_id => $fieldset)
                    /** @var \VladimirPopov\WebForms\Model\Field $field */
                    foreach ($fieldset['fields'] as $field) {
                        foreach ($result->getData() as $key => $value) {
                            if ($key == 'field_' . $field->getId() && strlen($value) && $field->getType() == 'select/contact') {
                                $target_field = ["id" => 'field_' . $field->getId(), 'logic_visibility' => $field->getData('logic_visibility')];

                                if ($this->getLogicTargetVisibility($target_field, $logic_rules, $result->getData('field'))) {
                                    $contactInfo = $field->getContactArray($value);
                                    if (strstr($contactInfo['email'], ',')) {
                                        $contactEmails = explode(',', $contactInfo['email']);
                                        foreach ($contactEmails as $cEmail) {
                                            $result->sendEmail('contact', ['name' => $contactInfo['name'], 'email' => $cEmail]);
                                        }
                                    } else {
                                        $result->sendEmail('contact', $contactInfo);
                                    }
                                }
                            }

                            if ($key == 'field_' . $field->getId() && $value && $field->getType() == 'subscribe') {
                                // subscribe to newsletter
                                $customer_email = $result->getCustomerEmail();
                                foreach ($customer_email as $email)
                                    $this->_subscriberFactory->create()->subscribe($email);
                            }
                        }
                    }

            }
            $result->resizeImages();

            $this->dropzoneFactory->create()->cleanup();

            if (!$this->_webformsHelper->isAdmin()) {
                if (
                    $this->getData('delete_submissions')
                    || ($this->getData('show_gdpr_agreement_checkbox')
                        && $this->getData('gdpr_agreement_checkbox_do_not_store')
                        && empty($postData['gdpr']))
                ) {
                    $result->delete();
                }
            }

            return $result;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return false;
        }
    }

    public function getUploadFields()
    {
        $upload_fields = [];
        foreach ($this->getFieldsToFieldsets() as $fieldset_id => $fieldset) {
            if (isset($fieldset['fields']))
                foreach ($fieldset['fields'] as $field) {
                    if ($field->getType() == 'file' || $field->getType() == 'image')
                        $upload_fields[] = $field->getId();
                }
        }
        return $upload_fields;
    }

    public function getUploadedFiles()
    {
        $uploaded_files = [];
        $upload_fields  = $this->getUploadFields();
        foreach ($upload_fields as $field_id) {
            $file_id  = 'file_' . $field_id;
            $uploader = new \Zend_Validate_File_Upload;
            $valid    = $uploader->isValid($file_id);
            if ($valid) {
                $file                      = $uploader->getFiles($file_id);
                $uploaded_files[$field_id] = $file[$file_id];
            }
        }
        return $uploaded_files;
    }

    public function getStatusEmailTemplateId($status)
    {
        switch ($status) {
            case \VladimirPopov\WebForms\Model\Result::STATUS_APPROVED:
                return $this->getEmailTemplateApproved();
            case \VladimirPopov\WebForms\Model\Result::STATUS_NOTAPPROVED:
                return $this->getEmailTemplateNotapproved();
            case \VladimirPopov\WebForms\Model\Result::STATUS_COMPLETED:
                return $this->getEmailTemplateCompleted();
        }
    }

    public function getUploadLimit($type = 'file')
    {
        $upload_limit = $this->_scopeConfig->getValue('webforms/files/upload_limit');
        if ($this->getFilesUploadLimit())
            $upload_limit = $this->getFilesUploadLimit();
        if ($type == 'image') {
            $upload_limit = $this->_scopeConfig->getValue('webforms/images/upload_limit');
            if ($this->getImagesUploadLimit())
                $upload_limit = $this->getImagesUploadLimit();
        }
        return intval($upload_limit);
    }

    public function duplicate()
    {
        // duplicate form
        $form = $this->_formFactory->create()
            ->setData($this->getData())
            ->setId(null)
            ->setName($this->getName() . ' ' . __('(new copy)'))
            ->setIsActive(false)
            ->setCreatedTime($this->_localDate->gmtDate())
            ->setUpdateTime($this->_localDate->gmtDate())
            ->save();

        // duplicate store data
        $stores = $this->_storeFactory->create()
            ->getCollection()
            ->addFilter('entity_id', $this->getId())
            ->addFilter('entity_type', $this->getEntityType());

        foreach ($stores as $store) {
            $this->_storeFactory->create()
                ->setData($store->getData())
                ->setId(null)
                ->setEntityId($form->getId())
                ->save();
        }

        $fieldset_update = [];
        $field_update    = [];

        // duplicate fieldsets and fields
        $fields_to_fieldsets = $this->getFieldsToFieldsets(true);
        foreach ($fields_to_fieldsets as $fieldset_id => $fieldset) {
            if ($fieldset_id) {
                $fs              = $this->_fieldsetFactory->create()->load($fieldset_id);
                $new_fieldset    = $this->_fieldsetFactory->create()
                    ->setData($fs->getData())
                    ->setId(null)
                    ->setCreatedTime($this->_localDate->gmtDate())
                    ->setUpdateTime($this->_localDate->gmtDate())
                    ->setWebformId($form->getId())
                    ->save();
                $new_fieldset_id = $new_fieldset->getId();

                $fieldset_update[$fieldset_id] = $new_fieldset_id;

                // duplicate store data
                $stores = $this->_storeFactory->create()
                    ->getCollection()
                    ->addFilter('entity_id', $fs->getId())
                    ->addFilter('entity_type', $fs->getEntityType());

                foreach ($stores as $store) {
                    $this->_storeFactory->create()
                        ->setData($store->getData())
                        ->setId(null)
                        ->setEntityId($new_fieldset_id)
                        ->save();
                }
            } else {
                $new_fieldset_id = 0;
            }
            foreach ($fieldset['fields'] as $field) {
                $new_field = $this->_fieldFactory->create()
                    ->setData($field->getData())
                    ->setId(null)
                    ->setCreatedTime($this->_localDate->gmtDate())
                    ->setUpdateTime($this->_localDate->gmtDate())
                    ->setWebformId($form->getId())
                    ->setFieldsetId($new_fieldset_id)
                    ->save();

                $field_update[$field->getId()] = $new_field->getId();

                // duplicate store data
                $stores = $this->_storeFactory->create()
                    ->getCollection()
                    ->addFilter('entity_id', $field->getId())
                    ->addFilter('entity_type', $field->getEntityType());

                foreach ($stores as $store) {
                    $this->_storeFactory->create()
                        ->setData($store->getData())
                        ->setId(null)
                        ->setEntityId($new_field->getId())
                        ->save();
                }
            }
        }

        // duplicate logic
        $logic_rules = $this->getLogic();
        foreach ($logic_rules as $logic) {
            $new_field_id = $field_update[$logic->getFieldId()];
            $new_target   = [];
            foreach ($logic->getTarget() as $target) {
                foreach ($fieldset_update as $old_id => $new_id) {
                    if ($target == 'fieldset_' . $old_id)
                        $new_target[] = 'fieldset_' . $new_id;
                }
                foreach ($field_update as $old_id => $new_id) {
                    if ($target == 'field_' . $old_id)
                        $new_target[] = 'field_' . $new_id;
                }
            }
            $new_logic = $this->_logicFactory->create()
                ->setData($logic->getData())
                ->setId(null)
                ->setCreatedTime($this->_localDate->gmtDate())
                ->setUpdateTime($this->_localDate->gmtDate())
                ->setFieldId($new_field_id)
                ->setTarget($new_target)
                ->save();

            // duplicate store data
            $stores = $this->_storeFactory->create()
                ->getCollection()
                ->addFilter('entity_id', $logic->getId())
                ->addFilter('entity_type', $logic->getEntityType());

            foreach ($stores as $store) {
                $new_target = [];
                $store_data = $store->getStoreData();
                if (!empty($store_data['target']))
                    foreach ($store_data['target'] as $target) {
                        foreach ($fieldset_update as $old_id => $new_id) {
                            if ($target == 'fieldset_' . $old_id)
                                $new_target[] = 'fieldset_' . $new_id;
                        }
                        foreach ($field_update as $old_id => $new_id) {
                            if ($target == 'field_' . $old_id)
                                $new_target[] = 'field_' . $new_id;
                        }
                    }
                $store->setData('target', $new_target);
                $this->_storeFactory->create()
                    ->setData($store->getData())
                    ->setId(null)
                    ->setEntityId($new_logic->getId())
                    ->save();
            }
        }

        return $form;
    }

    public function toJson(array $arrAttributes = array())
    {
        $data = $this->getData();

        unset(
            $data['id'],
            $data['email_template_id'],
            $data['email_customer_template_id'],
            $data['email_reply_template_id'],
            $data['email_result_approved_template_id'],
            $data['email_result_completed_template_id'],
            $data['email_result_notapproved_template_id'],
            $data['customer_print_template_id'],
            $data['approved_print_template_id'],
            $data['completed_print_template_id'],
            $data['created_time'],
            $data['update_time'],
            $data['is_active'],
            $data['access_groups'],
            $data['access_groups_serialized'],
            $data['dashboard_groups'],
            $data['dashboard_groups_serialized'],
            $data['access_enable'],
            $data['dashboard_enable']);

        /* export store view data */

        $data['store_data'] = array();

        $storeDataArray = $this->_storeCollectionFactory->create()
            ->addFilter('entity_id', $this->getId())
            ->addFilter('entity_type', \VladimirPopov\WebForms\Model\ResourceModel\Form::ENTITY_TYPE);

        foreach ($storeDataArray as $storeData) {
            $storeCode                      = $this->_storeManager->getStore($storeData['store_id'])->getCode();
            $data['store_data'][$storeCode] = unserialize($storeData['store_data']);
        }

        $data['fields']    = array();
        $data['fieldsets'] = array();

        foreach ($this->getFieldsToFieldsets(true) as $fsId => $fsArray) {
            $fieldset         = $this->_fieldsetFactory->create()->load($fsId);
            $fsData           = $fieldset->getData();
            $fsData['tmp_id'] = $fsId;
            if ($fsId == 0) {
                foreach ($fsArray['fields'] as $field) {
                    $fData           = $field->getData();
                    $fData['tmp_id'] = $fData['id'];
                    unset(
                        $fData['id'],
                        $fData['webform_id'],
                        $fData['fieldset_id'],
                        $fData['created_time'],
                        $fData['update_time']
                    );
                    $fData['store_data'] = array();
                    $storeDataArray      = $this->_storeCollectionFactory->create()
                        ->addFilter('entity_id', $field->getId())
                        ->addFilter('entity_type', \VladimirPopov\WebForms\Model\ResourceModel\Field::ENTITY_TYPE);

                    foreach ($storeDataArray as $storeData) {
                        $storeCode                       = $this->_storeManager->getStore($storeData['store_id'])->getCode();
                        $fData['store_data'][$storeCode] = unserialize($storeData['store_data']);
                    }

                    $data['fields'][] = $fData;
                }
            } else {
                unset(
                    $fsData['id'],
                    $fsData['webform_id'],
                    $fsData['created_time'],
                    $fsData['update_time']
                );
                $fsData['store_data'] = array();
                $storeDataArray       = $this->_storeCollectionFactory->create()
                    ->addFilter('entity_id', $fieldset->getId())
                    ->addFilter('entity_type', \VladimirPopov\WebForms\Model\ResourceModel\Fieldset::ENTITY_TYPE);

                foreach ($storeDataArray as $storeData) {
                    $storeCode                        = $this->_storeManager->getStore($storeData['store_id'])->getCode();
                    $fsData['store_data'][$storeCode] = unserialize($storeData['store_data']);
                }

                $fsData['fields'] = array();
                foreach ($fsArray['fields'] as $field) {
                    $fData           = $field->getData();
                    $fData['tmp_id'] = $fData['id'];
                    unset(
                        $fData['id'],
                        $fData['webform_id'],
                        $fData['fieldset_id'],
                        $fData['created_time'],
                        $fData['update_time']
                    );
                    $fData['store_data'] = array();
                    $storeDataArray      = $this->_storeCollectionFactory->create()
                        ->addFilter('entity_id', $field->getId())
                        ->addFilter('entity_type', \VladimirPopov\WebForms\Model\ResourceModel\Field::ENTITY_TYPE);

                    foreach ($storeDataArray as $storeData) {
                        $storeCode                       = $this->_storeManager->getStore($storeData['store_id'])->getCode();
                        $fData['store_data'][$storeCode] = unserialize($storeData['store_data']);
                    }

                    $fsData['fields'][] = $fData;
                }
                $data['fieldsets'][] = $fsData;
            }
        }

        /* export logic */

        $data['logic'] = array();

        $logic = $this->getLogic();
        foreach ($logic as $l) {
            $lData = $l->getData();
            unset(
                $lData['id'],
                $lData['webform_id'],
                $lData['created_time'],
                $lData['value_serialized'],
                $lData['target_serialized'],
                $lData['update_time']
            );
            $lData['store_data'] = array();
            $storeDataArray      = $this->_storeCollectionFactory->create()
                ->addFilter('entity_id', $l->getId())
                ->addFilter('entity_type', \VladimirPopov\WebForms\Model\ResourceModel\Logic::ENTITY_TYPE);

            foreach ($storeDataArray as $storeData) {
                $storeCode                       = $this->_storeManager->getStore($storeData['store_id'])->getCode();
                $lData['store_data'][$storeCode] = unserialize($storeData['store_data']);
            }
            $data['logic'][] = $lData;
        }
        return json_encode($data);
    }

    public function parseJson($jsonData)
    {
        $errors   = array();
        $warnings = array();

        $data = json_decode($jsonData, true);

        if (!$data) {
            $errors[] = __('Incorrect JSON data');
            return array('errors' => $errors, 'warnings' => $warnings);
        }

        if (empty($data["name"]))
            $errors[] = __('Missing form name');

        if (!empty($data["fields"])) {
            foreach ($data["fields"] as $field) {
                if (empty($field["name"]))
                    $errors[] = __('Missing field name');
                if (empty($field["type"]))
                    $errors[] = __('Field type not defined');
            }
            if (!empty($field['store_data'])) {
                foreach ($field['store_data'] as $storeCode => $storeData) {
                    $storeExists = $this->_webformsHelper->checkStoreCode($storeCode);
                    if (!$storeExists) {
                        $text = __('Store view contained within data not found: %1', $storeCode);
                        if (!in_array($text, $warnings))
                            $warnings[] = $text;
                    }
                }
            }
        }

        if (!empty($data["fieldsets"])) {
            foreach ($data["fieldsets"] as $fieldset) {
                if (empty($fieldset["name"]))
                    $errors[] = __('Fieldset found and missing name');
                if (!empty($fieldset["fields"])) {
                    foreach ($fieldset["fields"] as $field) {
                        if (empty($field["name"]))
                            $errors[] = __('Missing field name');
                        if (empty($field["type"]))
                            $errors[] = __('Field type not defined');
                        if (!empty($field['store_data'])) {
                            foreach ($field['store_data'] as $storeCode => $storeData) {
                                $storeExists = $this->_webformsHelper->checkStoreCode($storeCode);
                                if (!$storeExists) {
                                    $text = __('Store view contained within data not found: %1', $storeCode);
                                    if (!in_array($text, $warnings))
                                        $warnings[] = $text;
                                }
                            }
                        }
                    }
                }
                if (!empty($fieldset['store_data'])) {
                    foreach ($fieldset['store_data'] as $storeCode => $storeData) {
                        $storeExists = $this->_webformsHelper->checkStoreCode($storeCode);
                        if (!$storeExists) {
                            $text = __('Store view contained within data not found: %1', $storeCode);
                            if (!in_array($text, $warnings))
                                $warnings[] = $text;
                        }
                    }
                }
            }
        }

        if (!empty($data['store_data'])) {
            foreach ($data['store_data'] as $storeCode => $storeData) {
                $storeExists = $this->_webformsHelper->checkStoreCode($storeCode);
                if (!$storeExists) {
                    $text = __('Store view contained within data not found: %1', $storeCode);
                    if (!in_array($text, $warnings))
                        $warnings[] = $text;
                }
            }
        }

        if (!empty($data['logic'])) {
            foreach ($data['logic'] as $l) {
                if (empty($l['field_id']))
                    $warnings[] = __('Logic rule is missing trigger field');
                if (empty($l['value']))
                    $warnings[] = __('Logic rule is missing value');
                if (empty($l['target']))
                    $warnings[] = __('Logic rule is missing target');
                if (empty($l['action']))
                    $warnings[] = __('Logic rule is missing action');

                if (!empty($l['store_data'])) {
                    foreach ($l['store_data'] as $storeCode => $storeData) {
                        $storeExists = $this->_webformsHelper->checkStoreCode($storeCode);
                        if (!$storeExists) {
                            $text = __('Store view contained within data not found: %1', $storeCode);
                            if (!in_array($text, $warnings))
                                $warnings[] = $text;
                        }
                    }
                }
            }
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    public function import($jsonData)
    {
        $parse = $this->parseJson($jsonData);

        if ($parse['errors'])
            return $this;

        $data = json_decode($jsonData, true);
        $this->setData($data);
        $this->save();

        // transitional matrix for logic rules
        $logicMatrix = array();

        if ($this->getId()) {

            // import fields
            if (!empty($data['fields'])) {

                foreach ($data['fields'] as $fieldData) {

                    /** @var \VladimirPopov\WebForms\Model\Field $fieldModel */
                    $fieldModel = $this->_fieldFactory->create()->setData($fieldData);
                    $fieldModel->setData('webform_id', $this->getId());
                    $fieldModel->save();
                    $logicMatrix['field_' . $fieldData['tmp_id']] = $fieldModel->getId();

                    // import store data
                    if (!empty($fieldData['store_data'])) {
                        foreach ($fieldData['store_data'] as $storeCode => $storeData) {
                            $storeExists = $this->_webformsHelper->checkStoreCode($storeCode);
                            if ($storeExists) {
                                $storeId = $this->_storeManager->getStore($storeCode)->getId();
                                if ($storeId) {
                                    $fieldModel->saveStoreData($storeId, $storeData);
                                }
                            }
                        }
                    }
                }
            }

            // import fieldsets
            if (!empty($data['fieldsets'])) {

                foreach ($data['fieldsets'] as $fieldsetData) {

                    /** @var \VladimirPopov\WebForms\Model\Fieldset $fieldsetModel */
                    $fieldsetModel = $this->_fieldsetFactory->create()->setData($fieldsetData);
                    $fieldsetModel->setData('webform_id', $this->getId());
                    $fieldsetModel->save();
                    $logicMatrix['fieldset_' . $fieldsetData['tmp_id']] = $fieldsetModel->getId();

                    // import store data
                    if (!empty($fieldsetData['store_data'])) {
                        foreach ($fieldsetData['store_data'] as $storeCode => $storeData) {
                            $storeExists = $this->_webformsHelper->checkStoreCode($storeCode);
                            if ($storeExists) {
                                $storeId = $this->_storeManager->getStore($storeCode)->getId();
                                if ($storeId) {
                                    $fieldsetModel->saveStoreData($storeId, $storeData);
                                }
                            }
                        }
                    }

                    if (!empty($fieldsetData['fields'])) {
                        foreach ($fieldsetData['fields'] as $fieldData) {

                            /** @var \VladimirPopov\WebForms\Model\Field $fieldModel */
                            $fieldModel = $this->_fieldFactory->create()->setData($fieldData);
                            $fieldModel->setData('fieldset_id', $fieldsetModel->getId());
                            $fieldModel->setData('webform_id', $this->getId());
                            $fieldModel->save();
                            $logicMatrix['field_' . $fieldData['tmp_id']] = $fieldModel->getId();

                            // import store data
                            if (!empty($fieldData['store_data'])) {
                                foreach ($fieldData['store_data'] as $storeCode => $storeData) {
                                    $storeExists = $this->_webformsHelper->checkStoreCode($storeCode);
                                    if ($storeExists) {
                                        $storeId = $this->_storeManager->getStore($storeCode)->getId();
                                        if ($storeId) {
                                            $fieldModel->saveStoreData($storeId, $storeData);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // import logic rules
            if (!empty($data['logic'])) {

                foreach ($data['logic'] as $logicData) {

                    /** @var \VladimirPopov\WebForms\Model\Logic $logicModel */
                    $logicModel = $this->_logicFactory->create()->setData($logicData);
                    $logicModel->setData('field_id', $logicMatrix['field_' . $logicData['field_id']]);
                    $target = array();
                    foreach ($logicData['target'] as $targetData) {
                        $prefix = 'field_';
                        if (strstr($targetData, 'fieldset_')) $prefix = 'fieldset_';
                        if (!empty($logicMatrix[$targetData])) $target[] = $prefix . $logicMatrix[$targetData];
                        if ($targetData == 'submit') $target[] = 'submit';
                    }

                    $logicModel->setData('target', $target);
                    $logicModel->save();

                    // import store data
                    if (!empty($logicData['store_data'])) {
                        foreach ($logicData['store_data'] as $storeCode => $storeData) {
                            $storeExists = $this->_webformsHelper->checkStoreCode($storeCode);
                            if ($storeExists) {
                                $storeId = $this->_storeManager->getStore($storeCode)->getId();

                                if ($storeId) {
                                    $target = array();
                                    foreach ($storeData['target'] as $targetData) {
                                        $prefix = 'field_';
                                        if (strstr($targetData, 'fieldset_')) $prefix = 'fieldset_';
                                        if (!empty($logicMatrix[$targetData])) $target[] = $prefix . $logicMatrix[$targetData];
                                    }
                                    $storeData['target'] = $target;
                                    $logicModel->saveStoreData($storeId, $storeData);
                                }
                            }
                        }
                    }
                }
            }

            // import store data
            if (!empty($data['store_data'])) {
                foreach ($data['store_data'] as $storeCode => $storeData) {
                    $storeExists = $this->_webformsHelper->checkStoreCode($storeCode);
                    if ($storeExists) {
                        $storeId = $this->_storeManager->getStore($storeCode)->getId();
                        if ($storeId) {
                            $this->saveStoreData($storeId, $storeData);
                        }
                    }
                }
            }
        }
        return $this;
    }

    public function updateFieldPositions()
    {
        $collection = $this->_fieldCollectionFactory->create()
            ->addFilter('webform_id', $this->getId())->addOrder('position', 'asc');
        $i          = 1;
        foreach ($collection as $field) {
            $field->setPosition($i * 10);
            $field->save();
            $i++;
        }
    }

    public function updateFieldsetPositions()
    {
        $collection = $this->_fieldsetCollectionFactory->create()
            ->addFilter('webform_id', $this->getId())->addOrder('position', 'asc');
        $i          = 1;
        foreach ($collection as $fieldset) {
            $fieldset->setPosition($i * 10);
            $fieldset->save();
            $i++;
        }
    }
}
