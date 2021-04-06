<?php
/**
 * @author      Vladimir Popov
 * @copyright   Copyright © 2019 Vladimir Popov. All rights reserved.
 */

namespace VladimirPopov\WebForms\Controller\Adminhtml\Result;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class MassStatus extends \Magento\Backend\App\Action
{
//    const ID_FIELD = 'results';
    const ID_FIELD = 'selected';

    const REDIRECT_URL = '*/*/';

    const MODEL = 'VladimirPopov\WebForms\Model\Result';

    protected $redirect_params = ['_current' => true];

    protected $webformsHelper;
    protected $formFactory;

    protected $webformResultFactory;

    public function __construct(
        Action\Context $context,
        \VladimirPopov\WebForms\Helper\Data $webformsHelper,
        \VladimirPopov\WebForms\Model\FormFactory $formFactory,
        \VladimirPopov\WebForms\Model\ResultFactory $webformResultFactory
    )
    {
        $this->webformsHelper = $webformsHelper;
        $this->formFactory = $formFactory;
        $this->webformResultFactory = $webformResultFactory;
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        if ($this->getRequest()->getParam('webform_id')) {
            return $this->webformsHelper->isAllowed($this->getRequest()->getParam('webform_id'));
        }
        return $this->_authorization->isAllowed('VladimirPopov_WebForms::manage_forms');
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $Ids = [];
        if ($this->getRequest()->getParam('excluded') === 'false') {
            $webformId = $this->getRequest()->getParam('webform_id');

            if ($webformId) {
                $filters = $this->getRequest()->getParam('filters');
                /** @var \VladimirPopov\WebForms\Model\ResourceModel\Result\Collection $collection */
                $collection = $this->webformResultFactory->create()->getCollection()->addFilter('webform_id', $webformId);
                foreach ($filters as $fieldName => $value) {
                    if (strstr($fieldName, 'field_')) {
                        $fieldID = str_replace('field_', '', $fieldName);
                        $collection->addFieldFilter($fieldID, $value);
                    }
                }
                foreach ($collection as $result) {
                    $Ids[] = $result->getId();
                }
            }
        } else {
            $Ids = $this->getRequest()->getParam(static::ID_FIELD);
        }

        $status = $this->getRequest()->getParam('status');

        $formId = $this->getRequest()->getParam('webform_id');
        $modelForm = $this->formFactory->create()->load($formId);
        if (!is_array($Ids) || empty($Ids)) {
            $this->messageManager->addErrorMessage(__('Please select item(s).'));
        } else {
            try {
                foreach ($Ids as $id) {
                    $item = $this->webformResultFactory->create()->load($id);
                    $item->setApproved(intval($status))->save();

                    if ($modelForm->getEmailResultApproval()) {
                        $item->sendApprovalEmail();
                    }

                    $this->_eventManager->dispatch('webforms_result_approve', array('result' => $item));
                }
                $this->messageManager->addSuccessMessage(
                    __('Total of %1 result(s) have been updated.', count($Ids))
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if ($this->getRequest()->getParam('customer_id')) {
            return $resultRedirect->setPath('customer/index/edit', [
                'id' => $this->getRequest()->getParam('customer_id')
            ]);
        }
        return $resultRedirect->setPath(static::REDIRECT_URL, ['webform_id' => $formId]);
    }
}
