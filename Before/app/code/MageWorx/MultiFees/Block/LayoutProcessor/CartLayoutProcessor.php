<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\LayoutProcessor;

use MageWorx\MultiFees\Helper\Data as Helper;
use MageWorx\MultiFees\Helper\Fee as HelperFee;
use MageWorx\MultiFees\Helper\Price as HelperPrice;
use MageWorx\MultiFees\Model\ResourceModel\Fee\CartFeeCollectionFactory;
use MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollectionFactory;
use MageWorx\MultiFees\Block\LayoutProcessor;

/**
 * Class CartLayoutProcessor
 */
class CartLayoutProcessor extends LayoutProcessor
{
    /**
     * CartLayoutProcessor constructor.
     *
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $merger
     * @param Helper $helper
     * @param HelperFee $helperFee
     * @param HelperPrice $helperPrice
     * @param AbstractCollectionFactory $feeCollectionFactory
     * @param CartFeeCollectionFactory $cartFeeCollectionFactory
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
        CartFeeCollectionFactory $cartFeeCollectionFactory,
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
        $this->feeCollectionFactory = $cartFeeCollectionFactory;
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
            $jsLayout['components']['mageworx-fee-form-container']['children']
            ['mageworx-fee-form-fieldset']['children']
        )
        ) {
            $fieldSetPointer = &$jsLayout['components']['mageworx-fee-form-container']['children']
            ['mageworx-fee-form-fieldset']['children'];

            try {
                $cartFeeComponents = $this->getFeeComponents();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->critical($e->getLogMessage());
                $cartFeeComponents = [];
            }
            foreach ($cartFeeComponents as $component) {
                $fieldSetPointer[] = $component;
            }
        }

        if (isset(
            $jsLayout['components']['checkout']['children']['sidebar']['children']
            ['summary']['children']['itemsBefore']['children']['mageworx-fee-form-container']
            ['children']['mageworx-fee-form-fieldset']['children']
        )
        ) {
            $fieldSetPointer = &$jsLayout['components']['checkout']['children']['sidebar']['children']
            ['summary']['children']['itemsBefore']['children']['mageworx-fee-form-container']
            ['children']['mageworx-fee-form-fieldset']['children'];

            try {
                $cartFeeComponents = $this->getFeeComponents();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->critical($e->getLogMessage());
                $cartFeeComponents = [];
            }
            foreach ($cartFeeComponents as $component) {
                $fieldSetPointer[] = $component;
            }
        }

        return $jsLayout;
    }

    /**
     * Get components for the available cart fees
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getFeeComponents()
    {
        $components      = parent::getFeeComponents();
        $extraComponents = $this->getExtraComponents($this->feeCollection);

        $components = array_merge($components, $extraComponents);

        return $components;
    }
}
