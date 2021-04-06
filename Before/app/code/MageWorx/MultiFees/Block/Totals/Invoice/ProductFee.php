<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Totals\Invoice;

use Magento\Sales\Model\Order\Invoice as Source;
use MageWorx\MultiFees\Block\Totals\AbstractProductFeeTotal;

/**
 * Class ProductFee
 *
 * Renders product fee's totals in the invoice totals (incl. emails)
 *
 */
class ProductFee extends AbstractProductFeeTotal
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
