<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Adminhtml\Order\Create;

/**
 * Invoice view  comments form
 *
 */
class ProductFeeInfo extends \MageWorx\MultiFees\Block\AbstractProductFee
{
    /**
     * @var string
     */
    protected $_template = 'order/product_fee_details.phtml';

    /**
     * @return \Magento\Quote\Model\Quote|\Magento\Sales\Model\Order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSource()
    {
        return $this->getQuote();
    }

    /**
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCurrentItemId()
    {
        $itemId = null;
        $item = $this->getItem();

        if($item) {
            $itemId = $item->getQuoteItemId();
        }

        return $itemId;
    }
}
