<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Order;

/**
 * Invoice view  comments form
 *
 */
class ProductFeeInfo extends \MageWorx\MultiFees\Block\AbstractProductFee
{
    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getSource()
    {
        return $this->getOrder() ? : $this->getQuote();
    }
}
