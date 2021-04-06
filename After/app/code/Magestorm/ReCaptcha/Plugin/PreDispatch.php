<?php
/**
 * @author Magestorm Team
 * @copyright Copyright (c) 2017 Magestorm
 * @package Magestorm_ReCaptcha
 */


namespace Magestorm\ReCaptcha\Plugin;

use Magestorm\ReCaptcha\Helper\Data;

class PreDispatch
{
    /**
     * Google URl for checking captcha response
     */
    const GOOGLE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * @var \Magento\Backend\Model\View\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $redirect;

    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    private $responseFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * Predispatch constructor.
     * @param \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory
     * @param Data $helper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     */
    public function __construct(
        \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory,
        Data $helper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        $this->redirect = $redirect;
        $this->responseFactory = $responseFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\App\FrontControllerInterface $subject
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function aroundDispatch(
        \Magento\Framework\App\FrontControllerInterface $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        if ($this->helper->isEnabled() && $request->isPost()) {
            $check = $this->helper->getUrlsApplied($this->urlBuilder->getCurrentUrl());
            if ($check) {
                if ($this->helper->getCaptchaType() == 'default') {
                    $token = $request->getPost('g-recaptcha-response');
                    $checkCaptcha = $request->getPost('g-recaptcha-response', 'notExist');
                } else {
                    $token = $request->getPost('magestorm_invisible_token');
                    $checkCaptcha = $request->getPost('magestorm_invisible_token', 'notExist');
                }
                if (!$token) {
                    try {
                        $credentials = $this->jsonHelper->jsonDecode($request->getContent());
                        if (isset($credentials['g-recaptcha-response'])) {
                            $token = $credentials['g-recaptcha-response'];
                            $checkCaptcha = $credentials['g-recaptcha-response'];
                        } elseif (isset($credentials['magestorm_invisible_token'])) {
                            $token = $credentials['magestorm_invisible_token'];
                            $checkCaptcha = $credentials['magestorm_invisible_token'];
                        }
                    } catch (\Exception $e) {
                        $token = '';
                    }
                }
                if ($checkCaptcha != 'notExist') {
                    $validation = $this->verifyCaptcha($token);
                    if (!$validation) {
                        $this->messageManager->addErrorMessage(__('Incorrect reCAPTCHA'));
                        $response = $this->responseFactory->create();
                        $response->setRedirect($this->redirect->getRefererUrl());
                        $response->setNoCacheHeaders();
                        return $response;
                    }
                }
            }
        }
        $result = $proceed($request);

        return $result;
    }

    /**
     * @param string $token
     * @return bool
     */
    protected function verifyCaptcha($token)
    {
        if ($token) {
            $curlParams = [
                'secret' => $this->helper->getSecretKey(),
                'response' => $token
            ];
            $url = self::GOOGLE_VERIFY_URL . "?" . http_build_query($curlParams);
            $arrContextOptions = [
                "ssl" => [
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ]
            ];
            $result = $this->jsonHelper->jsonDecode(file_get_contents($url, false, stream_context_create($arrContextOptions)));
            $this->logger->critical($token);

            return $result['success'];
        }

        return false;
    }
}
