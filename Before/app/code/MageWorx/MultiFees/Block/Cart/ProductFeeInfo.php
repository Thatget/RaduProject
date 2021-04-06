<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Cart;

/**
 * Cart view  comments form
 *
 */
class ProductFeeInfo extends \MageWorx\MultiFees\Block\AbstractProductFee
{
    /**
     * @var string
     */
    protected $_template = 'MageWorx_MultiFees::cart/product_fee_info.phtml';

    /**
     * @return \Magento\Quote\Model\Quote
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
            $itemId = $item->getItemId();
        } else {
            $item = $this->getParentBlock()->getItem();
            $itemId = $item->getItemId();
        }

        return $itemId;
    }

    /**
     * @param \MageWorx\MultiFees\Api\Data\FeeInterface $item
     * @return $this
     */
    public function setItem($item)
    {
        $this->setData('item', $item);
        $this->helperProductFee->setCurrentItem($item);

        return $this;
    }
}
