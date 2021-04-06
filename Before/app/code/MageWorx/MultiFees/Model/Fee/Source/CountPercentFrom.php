<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Fee\Source;

use MageWorx\MultiFees\Model\AbstractFee;

class CountPercentFrom extends \MageWorx\MultiFees\Model\Source
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            ['value' => AbstractFee::FEE_COUNT_PERCENT_FROM_WHOLE_CART, 'label' => __('Whole Cart')],
            ['value' => AbstractFee::FEE_COUNT_PERCENT_FROM_PRODUCT, 'label' => __('Product')]
        ];
    }
}
