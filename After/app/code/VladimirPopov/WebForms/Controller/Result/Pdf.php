<?php
/**
 * @author      Vladimir Popov
 * @copyright   Copyright © 2019 Vladimir Popov. All rights reserved.
 */

namespace VladimirPopov\WebForms\Controller\Result;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Filesystem\DirectoryList;


class Pdf extends Action
{
    protected $session;

    protected $_fileFactory;

    protected $resultFactory;

    public function __construct(
        Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Customer\Model\Session $session,
        \VladimirPopov\WebForms\Model\ResultFactory $resultFactory
    )
    {
        $this->_fileFactory = $fileFactory;
        $this->session = $session;
        $this->resultFactory = $resultFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        // init model and delete
        $hash = $this->getRequest()->getParam('hash');
        if ($hash) {
            $id = $this->session->getData($hash);
            if ($id) {
                /** @var \VladimirPopov\WebForms\Model\Result $model */
                $model = $this->resultFactory->create()->load($id);
                if (@class_exists('\Mpdf\Mpdf')) {
                    $mpdf = @new \Mpdf\Mpdf(['mode' => 'utf-8']);
                    @$mpdf->WriteHTML($model->toPrintableHtml());
                    return $this->_fileFactory->create(
                        $model->getPdfFilename(),
                        @$mpdf->Output('', 'S'),
                        DirectoryList::TMP
                    );
                }
            }
        }
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        $resultForward->forward('noroute');
        return $resultForward;
    }
}
