<?php
/**
 * @author      Vladimir Popov
 * @copyright   Copyright © 2019 Vladimir Popov. All rights reserved.
 */

namespace VladimirPopov\WebForms\Controller\Adminhtml\Result;

use Magento\Framework\App\Filesystem\DirectoryList;

class PrintAction extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;
    protected $webformsHelper;

    protected $webformResultFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \VladimirPopov\WebForms\Helper\Data $webformsHelper,
        \VladimirPopov\WebForms\Model\ResultFactory $webformResultFactory

    )
    {
        $this->_fileFactory = $fileFactory;
        $this->webformsHelper = $webformsHelper;
        $this->webformResultFactory = $webformResultFactory;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        if ($this->getRequest()->getParam('id')) {
            $model = $this->webformResultFactory->create()->load($this->getRequest()->getParam('id'));
            return $this->webformsHelper->isAllowed($model->getWebformId());
        }
        return $this->_authorization->isAllowed('VladimirPopov_WebForms::manage_forms');
    }

    /**
     * Delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect | \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                // init model and delete
                /** @var \VladimirPopov\WebForms\Model\Result $model */
                $model = $this->webformResultFactory->create()->load($id);
                if (@class_exists('\Mpdf\Mpdf')) {
                    $mpdf = @new \Mpdf\Mpdf(['mode' => 'utf-8']);
                    @$mpdf->WriteHTML($model->toPrintableHtml());
                    return $this->_fileFactory->create(
                        $model->getPdfFilename(),
                        @$mpdf->Output('', 'S'),
                        DirectoryList::TMP
                    );
                }
                $this->messageManager->addWarning(__('Printing is disabled. Please install mPDF library. Run command: composer require mpdf/mpdf'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/form/');
    }
}
