<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Totals\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo as Source;
use MageWorx\MultiFees\Block\Totals\AbstractTotal;

/**
 * Class Fee
 *
 * Renders fee's totals in the credit memo totals (incl. emails)
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
        $source      = $totalsBlock->getCreditmemo();

        return $source;
    }
}
