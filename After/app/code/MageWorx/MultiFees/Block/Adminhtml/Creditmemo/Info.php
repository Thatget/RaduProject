<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Adminhtml\Creditmemo;

class Info extends \MageWorx\MultiFees\Block\AbstractFee
{
    /**
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getSource()
    {
        return $this->getCreditmemo();
    }
}
