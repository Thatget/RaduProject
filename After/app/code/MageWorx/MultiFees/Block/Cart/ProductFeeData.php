<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Cart;

use Magento\Framework\Pricing\PriceCurrencyInterface;

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
     * @return \MageWorx\MultiFees\Model\ResourceModel\Fee\ProductFeeCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getMultifees()
    {
        return $this->feeCollectionManager->getProductFeeCollection();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFeeData()
    {
        $feeData        = parent::getFeeData();
        $feeData['url'] = $this->getUrl('multifees/checkout/productFee');
        $currentItem    = $this->helperFee->getCurrentItem();

        if ($currentItem) {
            $feeData['quote_item_id'] = $currentItem->getItemId();
        }

        return $feeData;
    }
}
