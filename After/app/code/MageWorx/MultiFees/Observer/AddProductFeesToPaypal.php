<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddProductFeesToPaypal implements ObserverInterface
{
    const AMOUNT_SUBTOTAL = 'subtotal';

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * AddProductFeesToPaypal constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $cart  = $observer->getEvent()->getCart();
        $quote = $this->checkoutSession->getQuote();

        if ($cart instanceof \Magento\Paypal\Model\Cart &&
            method_exists($cart, 'addCustomItem')
        ) {
            $feePrice = $this->_getMageWorxFeeAmount($quote);

            $productFeePrice = $this->_getMageWorxProductFeeAmount($quote);
            if ($feePrice > 0) {
                $feeBaseTax = $quote->getShippingAddress()->getData('base_mageworx_fee_tax_amount');
                $cart->addCustomItem(__("Fee"), 1, $feePrice - $feeBaseTax);
            }


            if ($productFeePrice > 0) {
                $productFeeBaseTax = $quote->getShippingAddress()->getData('base_mageworx_product_fee_tax_amount');
                $cart->addCustomItem(__("Product Fee"), 1, $productFeePrice - $productFeeBaseTax);
            }
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return float
     */
    protected function _getMageWorxFeeAmount($quote)
    {
        $feeBasePrice = $quote->getShippingAddress()->getData('base_mageworx_fee_amount');

        return $feeBasePrice;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return float
     */
    protected function _getMageWorxProductFeeAmount($quote)
    {
        $feeBasePrice = $quote->getShippingAddress()->getData('base_mageworx_product_fee_amount');

        return $feeBasePrice;
    }
}
