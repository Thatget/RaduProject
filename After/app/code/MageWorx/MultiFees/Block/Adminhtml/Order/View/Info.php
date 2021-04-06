<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Adminhtml\Order\View;

class Info extends \MageWorx\MultiFees\Block\AbstractFee
{
    /**
     * @var string
     */
    protected $_template = 'order/product_fee_details.phtml';

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getSource()
    {
        return $this->getOrder();
    }

    /**
     * Retrieve order model
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('sales_order');
    }
}
