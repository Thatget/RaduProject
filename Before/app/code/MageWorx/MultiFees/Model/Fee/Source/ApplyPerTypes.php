<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Fee\Source;

use MageWorx\MultiFees\Model\AbstractFee;

class ApplyPerTypes extends \MageWorx\MultiFees\Model\Source
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            ['value' => AbstractFee::FEE_APPLY_PER_ITEM, 'label' => __('Each X Items')],
            ['value' => AbstractFee::FEE_APPLY_PER_PRODUCT, 'label' => __('Each X Products')],
            ['value' => AbstractFee::FEE_APPLY_PER_WEIGHT, 'label' => __('Each X Unit of Weight')],
            ['value' => AbstractFee::FEE_APPLY_PER_AMOUNT, 'label' => __('Each X Amount Spent')],
        ];
    }
}
