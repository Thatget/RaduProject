<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Adminhtml\Creditmemo;

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
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getSource()
    {
        return $this->getCreditmemo();
    }
}
