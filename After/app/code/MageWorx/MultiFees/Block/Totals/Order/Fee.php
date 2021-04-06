<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Totals\Order;

use Magento\Sales\Model\Order as Source;
use MageWorx\MultiFees\Block\Totals\AbstractTotal;

/**
 * Class Fee
 *
 * Renders fee's totals in the order totals (incl. emails)
 *
 */
class Fee extends AbstractTotal
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
