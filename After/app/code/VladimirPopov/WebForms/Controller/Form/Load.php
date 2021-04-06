<?php
/**
 * @author      Vladimir Popov
 * @copyright   Copyright © 2019 Vladimir Popov. All rights reserved.
 */

namespace VladimirPopov\WebForms\Controller\Form;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\HttpFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Session\SessionManagerInterface;
use VladimirPopov\WebForms\Helper\Data as WebformsHelper;

class Load extends Action
{
    /**
     * @var HttpFactory
     */
    protected $httpFactory;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var SessionManagerInterface
     */
    protected $_session;

    /**
     * @var WebformsHelper
     */
    protected $webformsHelper;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        SessionManagerInterface $_session,
        HttpFactory $httpFactory,
        WebformsHelper $webformsHelper
    )
    {
        $this->httpFactory    = $httpFactory;
        $this->pageFactory    = $pageFactory;
        $this->_session       = $_session;
        $this->webformsHelper = $webformsHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultHttp = $this->httpFactory->create();
        $resultHttp->setNoCacheHeaders();
        $resultHttp->setHeader('Content-Type', 'text/html', true);

        $request = $this->getRequest();
        $key     = $request->getParam('key');
        $id      = $request->getParam('webform_id');

        if (!$key) {
            return $resultHttp->setContent(__('Missing widget key.'));
        }

        $page  = $this->pageFactory->create();
        $block = $page->getLayout()->createBlock('VladimirPopov\WebForms\Block\Form', null, ['data' => [
            'webform_id' => $id,
            'template' => $request->getParam('template'),
            'after_submission_form' => $request->getParam('after_submission_form'),
            'focus' => $request->getParam('focus'),
            'scroll_to' => $request->getParam('scroll_to'),
        ]]);

        return $resultHttp->setContent($block->toHtml());
    }
}
