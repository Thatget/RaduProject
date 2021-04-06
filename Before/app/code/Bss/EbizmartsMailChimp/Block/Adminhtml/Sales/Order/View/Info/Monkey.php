<?php

namespace Bss\EbizmartsMailChimp\Block\Adminhtml\Sales\Order\View\Info;

class Monkey extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
	private  $assetRepository;

	private $requestInterfase;

	private $campaignName = null;

	private $storeManager;

	private $helper;

	public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        array $data = []
    ) {
    	$this->assetRepository = $assetRepository;
    	$this->requestInterfase = $requestInterface;
    	$this->storeManager = $storeManager;
    	$this->helper = $helper;
    	$this->logger = $context->getLogger();
        parent::__construct(
        	$context,
        	$registry,
        	$adminHelper,
        	$data
        );
    }

	public function isReferred()
    {
        $order = $this->getOrder();
        $ret = false;
        if ($order->getMailchimpFlag()) {
            $ret = true;
        }

        return $ret;
    }

    public function getFreddieImage()
    {
    	$params = ['_secure' => $this->requestInterfase->isSecure()];
    	$url = $this->assetRepository->getUrlWithParams('Ebizmarts_MailChimp::images/freddie.png', $params);
    	return $url;
    }

    private function getCampaignId()
    {
        $order = $this->getOrder();
        return $order->getMailchimpCampaignId();
    }

    private function getCampaignName()
    {
        if (!$this->campaignName) {
            $campaignId = $this->getCampaignId();
            $order = $this->getOrder();
            $storeId = $order->getStoreId();
            if ($this->isMailChimpEnabled($storeId)) {
                $this->campaignName = $this->getMailChimpCampaignNameById($campaignId, $storeId);
            }
        }

        return $this->campaignName;
    }

    public function isDataAvailable()
    {
        $dataAvailable = false;
        $campaignName = $this->getCampaignName();

        if ($campaignName) {
            $dataAvailable = true;
        }

        return $dataAvailable;
    }

    public function getStoreCodeFromOrder()
    {
    	$storeId = $this->getOrder()->getStoreId();
    	$store = $this->storeManager->getStore($storeId);
        return $store->getCode();
    }

    private function isMailChimpEnabled($storeId)
    {
    	return $this->helper->getConfigValue(
    		\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ECOMMERCE_ACTIVE,
    		$storeId
    	);
    }

    public function getMailChimpCampaignNameById($campaignId, $scopeId, $scope = 'stores')
    {
        $campaignName = null;
        $api = $this->helper->getApi($scopeId);

        try {
            $campaignData = $api->campaigns->get($campaignId);
            if (isset($campaignData['settings'])) {
                if (isset($campaignData['settings']['title'])) {
                    $campaignName = $campaignData['settings']['title'];
                }
                if ($campaignName == '' && isset($campaignData['settings']['subject_line'])) {
                    $campaignName = $campaignData['settings']['subject_line'];
                }
            }
        } catch(\Exception $e) {
            $this->logger->critical($e);
        }
        return $campaignName;
    }
}
