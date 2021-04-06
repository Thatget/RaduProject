<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Position implements ArrayInterface
{

    const POSITION_BELOW = 1;
    const POSITION_ABOVE = 2;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::POSITION_BELOW,
                'label' => 'Below Add to Cart'
            ],
            [
                'value' => self::POSITION_ABOVE,
                'label' => 'Above Add to Cart'
            ],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $_tmpOptions = $this->toOptionArray();
        $_options = [];
        foreach ($_tmpOptions as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }
}
