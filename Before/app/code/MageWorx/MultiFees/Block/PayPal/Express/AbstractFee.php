<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\PayPal\Express;

/**
 * Class AbstractFee
 */
abstract class AbstractFee extends \MageWorx\MultiFees\Block\Checkout\AbstractFeeData
{
    /**
     * @var \Magento\Checkout\Model\CompositeConfigProvider
     */
    protected $configProvider;

    /**
     * @var array|\Magento\Checkout\Block\Checkout\LayoutProcessorInterface[]
     */
    protected $layoutProcessors;

    /**
     * CartFees constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \MageWorx\MultiFees\Helper\Fee $helperFee
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     * @param \MageWorx\MultiFees\Helper\Price $helperPrice
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Session\SessionManager $sessionManager
     * @param \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feeCollectionManager
     * @param \Magento\Checkout\Model\CompositeConfigProvider $configProvider
     * @param array $layoutProcessors
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MageWorx\MultiFees\Helper\Fee $helperFee,
        \MageWorx\MultiFees\Helper\Data $helperData,
        \MageWorx\MultiFees\Helper\Price $helperPrice,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feeCollectionManager,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $helperFee,
            $helperData,
            $helperPrice,
            $checkoutSession,
            $currency,
            $customerSession,
            $priceCurrency,
            $sessionManager,
            $feeCollectionManager,
            $data
        );

        $this->configProvider = $configProvider;

        if (!empty($layoutProcessors)) {
            $this->layoutProcessors = $layoutProcessors;
        } elseif (isset($data['layoutProcessors'])) {
            $this->layoutProcessors = $data['layoutProcessors'];
            unset($data['layoutProcessors']);
        }
    }

    /**
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->helperData->isEnable() ?: false;
    }

    /**
     * Retrieve checkout configuration
     *
     * @return array
     */
    public function getCheckoutConfig()
    {
        return $this->configProvider->getConfig();
    }

    /**
     * Retrieve serialized JS layout configuration ready to use in template
     *
     * @return string
     */
    public function getJsLayout()
    {
        foreach ($this->layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }

        return \Zend_Json::encode($this->jsLayout);
    }

    /**
     * Get base url for block.
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}