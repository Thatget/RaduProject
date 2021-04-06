<?php

namespace Bss\MageWorxMultiFees\Helper;

use MageWorx\MultiFees\Model\AbstractFee as FeeModel;

class Fee extends \MageWorx\MultiFees\Helper\Fee
{
	public function getBaseFeeLeft($total, array $totalsArray, $fee, $validItems)
    {
        $baseMageWorxFeeLeft = 0;

        if ($fee->getCountPercentFrom() == FeeModel::FEE_COUNT_PERCENT_FROM_PRODUCT) {
            foreach ($validItems as $item) {
                $baseMageWorxFeeLeft += $item->getPrice();
            }

            return $baseMageWorxFeeLeft;
        }

        $baseSubtotal = floatval($total->getBaseSubtotal());
        $baseShipping = floatval($total->getBaseShippingAmount()); // - $address->getBaseShippingTaxAmount()
        $baseTax      = floatval($total->getBaseTaxAmount());

        foreach ($totalsArray as $field) {
            switch ($field) {
                case 'subtotal':
                    $baseMageWorxFeeLeft += $baseSubtotal;
                    break;
                case 'shipping':
                    $baseMageWorxFeeLeft += $baseShipping;
                    break;
                case 'tax':
                    $baseMageWorxFeeLeft += $baseTax;
                    break;
            }
        }

        return $baseMageWorxFeeLeft;
    }
}