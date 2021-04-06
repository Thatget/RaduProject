<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\LayoutProcessor;

use MageWorx\MultiFees\Api\Data\FeeInterface;
use MageWorx\MultiFees\Helper\Data as Helper;
use MageWorx\MultiFees\Helper\Fee as HelperFee;
use MageWorx\MultiFees\Helper\ProductFee as HelperProductFee;
use MageWorx\MultiFees\Helper\Price as HelperPrice;
use MageWorx\MultiFees\Helper\ProductFeePrice as HelperProductFeePrice;
use MageWorx\MultiFees\Model\ResourceModel\Fee\ProductFeeCollectionFactory;
use MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollectionFactory;
use MageWorx\MultiFees\Block\LayoutProcessor;

class ProductLayoutProcessor extends LayoutProcessor
{
    /**
     * ProductLayoutProcessor constructor.
     *
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $merger
     * @param Helper $helper
     * @param HelperFee $helperFee
     * @param HelperProductFee $helperProductFee
     * @param HelperPrice $helperPrice
     * @param HelperProductFeePrice $helperProductFeePrice
     * @param AbstractCollectionFactory $feeCollectionFactory
     * @param ProductFeeCollectionFactory $productFeeCollectionFactory
     * @param \MageWorx\MultiFees\Block\FeeFormInputPlant $feeFormInputRendererFactory
     * @param \Magento\Framework\Escaper $escaper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Checkout\Block\Checkout\AttributeMerger $merger,
        Helper $helper,
        HelperFee $helperFee,
        HelperProductFee $helperProductFee,
        HelperPrice $helperPrice,
        HelperProductFeePrice $helperProductFeePrice,
        AbstractCollectionFactory $feeCollectionFactory,
        ProductFeeCollectionFactory $productFeeCollectionFactory,
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
        $this->feeCollectionFactory = $productFeeCollectionFactory;
        $this->helperFee            = $helperProductFee;
        $this->helperPrice          = $helperProductFeePrice;
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
        if ($this->helperFee->isProductPage()) {
            $this->helperFee->setCurrentItem($this->helperFee->getQuoteItemFromCurrentProduct());
        }

        $isApplyOnClick = $this->helper->isApplyOnClick();

        $itemId = '';
        $item = $this->helperFee->getCurrentItem();
        if ($item && !$this->helperFee->isProductPage()) {
            $itemId = $item->getItemId();
            if (isset($jsLayout['components']['mageworx-product-fee-form-container'])) {
                $jsLayout['components']['mageworx-product-fee-form-container' . $itemId] =
                    $jsLayout['components']['mageworx-product-fee-form-container'];
                $jsLayout['components']['mageworx-product-fee-form-container' . $itemId]['itemId'] = $itemId;
            }
        }

        if (isset($jsLayout['components']['mageworx-product-fee-form-container' . $itemId])) {
            $jsLayout['components']['mageworx-product-fee-form-container' . $itemId]['applyOnClick'] = $isApplyOnClick;
            $jsLayout['components']['mageworx-product-fee-form-container' . $itemId]['typeId']
                = FeeInterface::PRODUCT_TYPE;
        }

        if (isset($jsLayout['components']['mageworx-product-fee-form-container' . $itemId]['children']['mageworx-fee-form-fieldset']['children'])) {
            $fieldSetPointer = &$jsLayout['components']['mageworx-product-fee-form-container' . $itemId]['children']
            ['mageworx-fee-form-fieldset']['children'];

            try {
                $productFeeComponents = $this->getFeeComponents();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->critical($e->getLogMessage());
                $productFeeComponents = [];
            }
            foreach ($productFeeComponents as $component) {
                $fieldSetPointer[] = $component;
            }
            $jsLayout['components']['mageworx-product-fee-form-container' . $itemId]['feeCount'] = count($productFeeComponents);
        }

        if (isset($jsLayout['components']['mageworx-product-fee-form-container' . $itemId]['children']['mageworx-product-fee-form-fieldset']['children'])) {
            $fieldSetPointer = &$jsLayout['components']['mageworx-product-fee-form-container' . $itemId]['children']
            ['mageworx-product-fee-form-fieldset']['children'];

            try {
                $productFeeComponents = $this->getFeeComponents();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->critical($e->getLogMessage());
                $productFeeComponents = [];
            }
            foreach ($productFeeComponents as $component) {
                $fieldSetPointer[] = $component;
            }
        }

        return $jsLayout;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFeeComponents()
    {
        $currentQuoteItem = $this->helperFee->getCurrentItem();
        $components     = [];

        if (!$currentQuoteItem instanceof \Magento\Quote\Model\Quote\Item) {
            return $components;
        }

        $quote         = $this->helperFee->getQuote();
        $feeCollection = $this->getFeeCollection($quote->getStoreId());
        $feeCollection = $this->helperFee->validateFeeCollectionByQuoteItem($feeCollection, $currentQuoteItem);

        $this->feeCollection = $feeCollection;

        $components      = $this->convertFeeCollectionToComponentsArray($feeCollection);
        if (!$this->helperFee->isProductPage()) {
            $extraComponents = $this->getExtraComponents($feeCollection);
            $components      = array_merge($components, $extraComponents);
        }

        return $components;
    }

    /**
     * @param \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function convertFeeCollectionToComponentsArray(
        \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection
    ) {
        $details            = $this->helperFee->getQuoteDetailsMultifees();
        $currentQuoteItemId = $this->helperFee->getCurrentQuoteItemId();
        $productDetails     = [];

        if ($currentQuoteItemId) {
            foreach ($details as $feeId => $feeData) {
                foreach ($feeData as $itemId => $data) {
                    if ($itemId != $this->helperFee->getCurrentQuoteItemId()) {
                        continue;
                    }
                    $productDetails[$feeId] = $data;
                }
            }
        }

        $components = [];
        /** @var \MageWorx\MultiFees\Model\AbstractFee $fee */
        foreach ($feeCollection as $fee) {
            /** @var \MageWorx\MultiFees\Block\FeeFormInput\FeeFormInputRenderInterface $renderer */
            $fee->setCurrentQuoteItemId($this->helperFee->getCurrentQuoteItemId());
            $renderer     = $this->feeFormInputRendererFactory->create($fee, ['details' => $productDetails]);
            $components[] = $renderer->render();
        }

        return $components;
    }
}
