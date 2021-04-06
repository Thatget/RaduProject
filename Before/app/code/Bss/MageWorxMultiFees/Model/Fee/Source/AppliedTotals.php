<?php

namespace Bss\MageWorxMultiFees\Model\Fee\Source;

class AppliedTotals extends \MageWorx\MultiFees\Model\Fee\Source\AppliedTotals
{
    public function toOptionArray()
    {
        return [
            ['value' => 'subtotal', 'label' => __('Subtotal without Discount')],
            ['value' => 'shipping', 'label' => __('Shipping & Handling')],
            ['value' => 'tax', 'label' => __('Tax')],
        ];
    }
}
