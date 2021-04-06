<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Plugin\Quote;

use Magento\Quote\Api\Data\TotalSegmentExtensionFactory;

class FeeTaxDetailsPlugin
{
    /**
     * @var \MageWorx\MultiFees\Api\Data\FeeDetailsInterfaceFactory
     */
    protected $detailsFactory;

    /**
     * @var \MageWorx\MultiFees\Api\Data\FeeOptionsInterfaceFactory
     */
    protected $optionsFactory;

    /**
     * @var TotalSegmentExtensionFactory
     */
    protected $totalSegmentExtensionFactory;

    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    protected $helperData;

    /**
     * @var array
     */
    protected $codes;

    /**
     * FeeDetailsPlugin constructor.
     *
     * @param \MageWorx\MultiFees\Api\Data\FeeDetailsInterfaceFactory $detailsFactory
     * @param \MageWorx\MultiFees\Api\Data\FeeOptionsInterfaceFactory $optionsFactory
     * @param TotalSegmentExtensionFactory $totalSegmentExtensionFactory
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     */
    public function __construct(
        \MageWorx\MultiFees\Api\Data\FeeDetailsInterfaceFactory $detailsFactory,
        \MageWorx\MultiFees\Api\Data\FeeOptionsInterfaceFactory $optionsFactory,
        TotalSegmentExtensionFactory $totalSegmentExtensionFactory,
        \MageWorx\MultiFees\Helper\Data $helperData
    ) {
        $this->detailsFactory               = $detailsFactory;
        $this->optionsFactory               = $optionsFactory;
        $this->totalSegmentExtensionFactory = $totalSegmentExtensionFactory;
        $this->helperData                   = $helperData;
        $this->codes                        = ['mageworx_fee_tax', 'mageworx_product_fee_tax'];
    }

    /**
     * @param array $options
     * @param string $code
     * @return array
     */
    protected function getOptionsData($options, $code)
    {
        $feeOptions = [];
        foreach ($options as $option) {
            /** @var \MageWorx\MultiFees\Api\Data\FeeOptionsInterface $feeOption */
            $feeOption = $this->optionsFactory->create([]);
            $percent   = !empty($option['percent']) ? $option['percent'] : '';
            $feeOption->setPercent($percent);
            $feeOption->setTitle($option['title']);
            $feeOption->setPrice($option['price']);
            $feeOptions[] = $feeOption;
        }

        return $feeOptions;
    }

    /**
     * @param \Magento\Quote\Model\Cart\TotalsConverter $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Address\Total[] $addressTotals
     * @return \Magento\Quote\Api\Data\TotalSegmentInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function aroundProcess(
        \Magento\Quote\Model\Cart\TotalsConverter $subject,
        \Closure $proceed,
        array $addressTotals = []
    ) {
        $totalSegments = $proceed($addressTotals);

        foreach ($this->codes as $code) {
            if (!array_key_exists($code, $addressTotals)) {
                continue;
            }

            $fees = $addressTotals[$code]->getData();
            if (!array_key_exists('full_info', $fees)) {
                continue;
            }

            $fullInfo  = $fees['full_info'];
            if (is_string($fullInfo)) {
                $fullInfo = $this->helperData->unserializeValue($fullInfo);
            }

            if (!$fullInfo) {
                continue;
            }

            $finalData = $this->prepareFinalData($code, $fullInfo);

            $attributes = $totalSegments[$code]->getExtensionAttributes();
            if ($attributes === null) {
                $attributes = $this->totalSegmentExtensionFactory->create();
            }
            $attributes = $this->setDataToAttributes($attributes, $finalData, $code);
            $totalSegments[$code]->setExtensionAttributes($attributes);
        }

        return $totalSegments;
    }

    /**
     * @param string $code
     * @param array $fullInfo
     * @return array
     */
    protected function prepareFinalData($code, $fullInfo)
    {
        $finalData = [];

        if ($code == 'mageworx_fee_tax') {
            foreach ($fullInfo as $info) {
                $feeDetails = $this->detailsFactory->create([]);
                $feeOptions = $this->getOptionsData($info['options'], $code);
                $feeDetails->setOptions($feeOptions);
                $finalData[] = $feeDetails;
            }
        } else {
            foreach ($fullInfo as $feeInfo) {
                foreach ($feeInfo as $info) {
                    $feeDetails = $this->detailsFactory->create([]);
                    $feeOptions = $this->getOptionsData($info['options'], $code);
                    $feeDetails->setOptions($feeOptions);
                    $finalData[] = $feeDetails;
                }
            }
        }

        return $finalData;
    }

    /**
     * @param array $attributes
     * @param array $finalData
     * @param string $code
     * @return array
     */
    protected function setDataToAttributes($attributes, $finalData, $code)
    {
        if ($code == 'mageworx_product_fee_tax') {
            $attributes->setMageworxProductFeeDetails($finalData);
        } else {
            $attributes->setMageworxFeeDetails($finalData);
        }
        return $attributes;
    }
}
