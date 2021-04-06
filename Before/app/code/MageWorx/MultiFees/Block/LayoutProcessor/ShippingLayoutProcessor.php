<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\LayoutProcessor;

use MageWorx\MultiFees\Helper\Data as Helper;
use MageWorx\MultiFees\Helper\Fee as HelperFee;
use MageWorx\MultiFees\Helper\Price as HelperPrice;
use MageWorx\MultiFees\Model\ResourceModel\Fee\ShippingFeeCollectionFactory;
use MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollectionFactory;
use MageWorx\MultiFees\Block\LayoutProcessor;

class ShippingLayoutProcessor extends LayoutProcessor
{
    /**
     * ShippingLayoutProcessor constructor.
     *
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $merger
     * @param Helper $helper
     * @param HelperFee $helperFee
     * @param HelperPrice $helperPrice
     * @param AbstractCollectionFactory $feeCollectionFactory
     * @param ShippingFeeCollectionFactory $shippingFeeCollectionFactory
     * @param \MageWorx\MultiFees\Block\FeeFormInputPlant $feeFormInputRendererFactory
     * @param \Magento\Framework\Escaper $escaper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Checkout\Block\Checkout\AttributeMerger $merger,
        Helper $helper,
        HelperFee $helperFee,
        HelperPrice $helperPrice,
        AbstractCollectionFactory $feeCollectionFactory,
        ShippingFeeCollectionFactory $shippingFeeCollectionFactory,
        \MageWorx\MultiFees\Block\FeeFormInputPlant $feeFormInputRendererFactory,
        \Magento\Framework\Escaper $escaper,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $merger,
            $helper,
            $helperFee,
            $helperPrice,
            $feeCollectionFactory,
            $feeFormInputRendererFactory,
            $escaper,
            $logger
        );
        $this->feeCollectionFactory = $shippingFeeCollectionFactory;
    }

    /**
     * Add our multifees components to the layout if the specific container exists
     *
     * @param array $jsLayout
     * @return array
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     */
    public function process($jsLayout)
    {
        $jsLayout = $this->addApplyOnClickData($jsLayout);

        if (isset(
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shippingAdditional']['children']
            ['mageworx-shipping-fee-form-container']['children']['mageworx-shipping-fee-form-fieldset']['children']
        )
        ) {
            $isApplyOnClick                                          = $this->helper->isApplyOnClick();
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shippingAdditional']['children']
            ['mageworx-shipping-fee-form-container']['applyOnClick'] = $isApplyOnClick;
            $fieldSetPointer                                         = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shippingAdditional']['children']
            ['mageworx-shipping-fee-form-container']['children']['mageworx-shipping-fee-form-fieldset']['children'];

            try {
                $shippingFeeComponents = $this->getFeeComponents();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->critical($e->getLogMessage());
                $shippingFeeComponents = [];
            }
            foreach ($shippingFeeComponents as $component) {
                $fieldSetPointer[] = $component;
            }
        }

        if (isset(
            $jsLayout['components']['mageworx-shipping-fee-form-container']['children']
            ['mageworx-shipping-fee-form-fieldset']['children']
        )
        ) {
            $isApplyOnClick = $this->helper->isApplyOnClick();

            $jsLayout['components']['mageworx-shipping-fee-form-container']['applyOnClick'] = $isApplyOnClick;

            $fieldSetPointer = &$jsLayout['components']['mageworx-shipping-fee-form-container']['children']
            ['mageworx-shipping-fee-form-fieldset']['children'];

            try {
                $shippingFeeComponents = $this->getFeeComponents();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->critical($e->getLogMessage());
                $shippingFeeComponents = [];
            }
            foreach ($shippingFeeComponents as $component) {
                $fieldSetPointer[] = $component;
            }
        }

        return $jsLayout;
    }
}
