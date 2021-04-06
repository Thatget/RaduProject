<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Helper;

/**
 * Config ProductFeePrice helper
 */
class ProductFeePrice extends Price
{
    /**
     * ProductFeePrice constructor.
     *
     * @param \Magento\Tax\Model\Calculation $taxCalculator
     * @param Fee $helperFee
     * @param ProductFee $helperProductFee
     * @param Data $helperData
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Checkout\Helper\Cart $helperCart
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Tax\Model\Calculation $taxCalculator,
        Fee $helperFee,
        ProductFee $helperProductFee,
        Data $helperData,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Checkout\Helper\Cart $helperCart,
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($taxCalculator, $helperFee, $helperData, $priceCurrency, $helperCart, $context);
        $this->helperFee = $helperProductFee;
    }
}
