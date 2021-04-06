<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\LayoutProcessor;

use MageWorx\MultiFees\Helper\Data as Helper;
use MageWorx\MultiFees\Helper\Fee as HelperFee;
use MageWorx\MultiFees\Helper\Price as HelperPrice;
use MageWorx\MultiFees\Model\ResourceModel\Fee\PaymentFeeCollectionFactory;
use MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollectionFactory;
use MageWorx\MultiFees\Block\LayoutProcessor;

class PaymentLayoutProcessor extends LayoutProcessor
{
    /**
     * PaymentLayoutProcessor constructor.
     *
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $merger
     * @param Helper $helper
     * @param HelperFee $helperFee
     * @param HelperPrice $helperPrice
     * @param AbstractCollectionFactory $feeCollectionFactory
     * @param PaymentFeeCollectionFactory $paymentFeeCollectionFactory
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
        PaymentFeeCollectionFactory $paymentFeeCollectionFactory,
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
        $this->feeCollectionFactory = $paymentFeeCollectionFactory;
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
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['beforeMethods']['children']
            ['mageworx-payment-fee-form-container']['children']['mageworx-payment-fee-form-fieldset']['children']
        )
        ) {
            $isApplyOnClick = $this->helper->isApplyOnClick();
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['beforeMethods']['children']
            ['mageworx-payment-fee-form-container']['applyOnClick'] = $isApplyOnClick;
            $fieldSetPointer                                        = &$jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['beforeMethods']['children']
            ['mageworx-payment-fee-form-container']['children']['mageworx-payment-fee-form-fieldset']['children'];

            try {
                $paymentFeeComponents = $this->getFeeComponents();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->critical($e->getLogMessage());
                $paymentFeeComponents = [];
            }
            foreach ($paymentFeeComponents as $component) {
                $fieldSetPointer[] = $component;
            }
        }

        if (isset(
            $jsLayout['components']['mageworx-payment-fee-form-container']['children']
            ['mageworx-payment-fee-form-fieldset']['children']
        )
        ) {
            $isApplyOnClick = $this->helper->isApplyOnClick();

            $jsLayout['components']['mageworx-payment-fee-form-container']['applyOnClick'] = $isApplyOnClick;

            $fieldSetPointer = &$jsLayout['components']['mageworx-payment-fee-form-container']['children']
            ['mageworx-payment-fee-form-fieldset']['children'];

            try {
                $paymentFeeComponents = $this->getFeeComponents();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->critical($e->getLogMessage());
                $paymentFeeComponents = [];
            }
            foreach ($paymentFeeComponents as $component) {
                $fieldSetPointer[] = $component;
            }
        }

        return $jsLayout;
    }
}
