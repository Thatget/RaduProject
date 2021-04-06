<?php 

namespace Bss\RwMagentoCapcha\Plugin\Newsletter\Controller\Subscriber;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use MSP\ReCaptcha\Api\ValidateInterface;
use MSP\ReCaptcha\Model\IsCheckRequiredInterface;
use MSP\ReCaptcha\Model\Provider\FailureProviderInterface;
use MSP\ReCaptcha\Model\Provider\ResponseProviderInterface;
/**
 * 
 */

class NewAction
{
    // protected $resultRedirectFactory;
    // protected $urlBuilder;
    protected $messageManager;
    // private $redirect;
    private $responseFactory;
    // protected $jsonHelper;
    protected $validateInterface;
    protected $responseProvider;
    protected $remoteAddress;
    public function __construct(
        // \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory,
        // \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        // \Magento\Framework\App\Response\RedirectInterface $redirect,
        // \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \MSP\ReCaptcha\Api\ValidateInterface $validateInterface,
        \MSP\ReCaptcha\Model\Provider\ResponseProviderInterface $responseProvider,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
    ) {
        // $this->resultRedirectFactory = $resultRedirectFactory;
        // $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        // $this->redirect = $redirect;
        $this->responseFactory = $responseFactory;
        // $this->jsonHelper = $jsonHelper;
        $this->validateInterface = $validateInterface;
        $this->responseProvider = $responseProvider;
        $this->remoteAddress = $remoteAddress;

    }


	public function aroundExecute(
		\Magento\Newsletter\Controller\Subscriber\NewAction $subject,
		callable $proceed
	) 
	{
		$remoteIp = $this->remoteAddress->getRemoteAddress();
		$reCaptchaResponse = $this->responseProvider->execute();
		if($this->validateInterface->validate($reCaptchaResponse, $remoteIp))
		{
			return $proceed();
		}
		else
		{
			$this->messageManager->addErrorMessage(__('Incorrect reCAPTCHA'));
	        $response = $this->responseFactory->create();
	        $response->setRedirect($this->redirect->getRefererUrl());
	        $response->setNoCacheHeaders();
	        $subject->getResponse()->setRedirect($this->redirect->getRedirectUrl());
	        return false;
		}
	}
	
}