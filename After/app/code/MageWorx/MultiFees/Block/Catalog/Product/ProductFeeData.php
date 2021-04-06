<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Catalog\Product;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection;

class ProductFeeData extends \MageWorx\MultiFees\Block\Checkout\AbstractFeeData
{
    /**
     * ProductFeeData constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \MageWorx\MultiFees\Helper\Fee $helperFee
     * @param \MageWorx\MultiFees\Helper\ProductFee $helperProductFee
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     * @param \MageWorx\MultiFees\Helper\Price $helperPrice
     * @param \MageWorx\MultiFees\Helper\ProductFeePrice $helperProductFeePrice
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Magento\Customer\Model\Session $customerSession
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Session\SessionManager $sessionManager
     * @param \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feeCollectionManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MageWorx\MultiFees\Helper\Fee $helperFee,
        \MageWorx\MultiFees\Helper\ProductFee $helperProductFee,
        \MageWorx\MultiFees\Helper\Data $helperData,
        \MageWorx\MultiFees\Helper\Price $helperPrice,
        \MageWorx\MultiFees\Helper\ProductFeePrice $helperProductFeePrice,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Customer\Model\Session $customerSession,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feeCollectionManager,
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
        $this->helperFee   = $helperProductFee;
        $this->helperPrice = $helperProductFeePrice;
    }

    /**
     * @return \MageWorx\MultiFees\Model\ResourceModel\Fee\CartFeeCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getMultifees()
    {
        return $this->feeCollectionManager->getProductFeeCollection();
    }

    /**
     * Get specified fee data
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFeeData()
    {
        $fees = $this->getMultifees();
        // "Select Fee" block should be displayed only if there was a hidden fees inside
        $availableFeeCount = $fees->count(AbstractCollection::COUNT_HIDDEN_FEE);

        $fee          = $this->checkoutSession->getQuote()->getMageworxProductFee();
        $feeFormatted = strip_tags($this->priceCurrency->convertAndFormat($fee));
        $details      = $this->helperFee->getQuoteDetailsMultifees();

        $result                     = [];
        $result['is_enable']        = $this->getIsEnable() ? (bool)$availableFeeCount : false;
        $result['is_display_title'] = ($result['is_enable'] == false) ? false : $this->getIsDisplayTitle();
        $result['fee']              = $feeFormatted;
        $result['url']              = $this->getUrl('multifees/checkout/productFee');
        $result['is_valid']         = !isset($details['is_valid']) ? true : $details['is_valid'];
        $result['applyOnClick']     = $this->helperData->isApplyOnClick();

        return $result;
    }
}
