<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Plugin;

class ShowProductFeesForBundle
{
    /**
     * @var \MageWorx\MultiFees\Block\Order\ProductFeeInfo
     */
    protected $feeInfoBlock;

    /**
     * ShowProductFeesForBundle constructor.
     *
     * @param \MageWorx\MultiFees\Block\Order\ProductFeeInfo $feeInfoBlock
     */
    public function __construct(
        \MageWorx\MultiFees\Block\Order\ProductFeeInfo $feeInfoBlock
    )
    {
        $this->feeInfoBlock = $feeInfoBlock;
    }

    public function afterGetItemHtml(
        \Magento\Sales\Block\Items\AbstractItems $subject,
        $result,
        \Magento\Framework\DataObject $item
    ) {
        if ($item->getOrderItem()) {
            $type = $item->getOrderItem()->getProductType();
        } elseif ($item instanceof \Magento\Quote\Model\Quote\Address\Item) {
            $type = $item->getQuoteItem()->getProductType();
        } else {
            $type = $item->getProductType();
        }

        if ($type == 'bundle') {
            $this->feeInfoBlock->setItem($item);
            $result .= '<tr><td colspan="5">' . $this->feeInfoBlock->toHtml() . '</td></tr>';
        };

        return $result;
    }
}