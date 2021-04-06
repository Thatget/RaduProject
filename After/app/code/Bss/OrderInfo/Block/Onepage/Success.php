<?php

namespace Bss\OrderInfo\Block\Onepage;

use Magento\Checkout\Block\Onepage\Success as MagentoSuccess;

/**
 * One page checkout success page
 */
class Success extends MagentoSuccess{

    /**
     * Reutrn Shipping method code to success page
     *
     * @return string
     */
    public function getShippingMethod(){
        $order = $this->_checkoutSession->getLastRealOrder();
        return $order->getShippingMethod();
    }
}
