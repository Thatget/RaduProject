<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Adminhtml\Invoice;

class Info extends \MageWorx\MultiFees\Block\AbstractFee
{
    /**
     * @return \Magento\Sales\Model\Order\Invoice
     */
    public function getSource()
    {
        return $this->getInvoice();
    }
}
