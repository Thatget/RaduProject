<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Totals\Invoice;

use Magento\Sales\Model\Order\Invoice as Source;
use MageWorx\MultiFees\Block\Totals\AbstractTotal;

/**
 * Class Fee
 *
 * Renders fee's totals in the invoice totals (incl. emails)
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
        $source      = $totalsBlock->getInvoice();

        return $source;
    }
}
