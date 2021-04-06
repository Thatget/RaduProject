<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Totals\Order;

use Magento\Sales\Model\Order as Source;
use MageWorx\MultiFees\Block\Totals\AbstractProductFeeTotal;

class ProductFee extends AbstractProductFeeTotal
{
    /**
     * @return Source
     */
    protected function getRealSource()
    {
        $totalsBlock = $this->getParentTotalsBlock();
        $source      = $totalsBlock->getOrder();

        return $source;
    }


}
