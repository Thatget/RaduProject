<?php

namespace Bss\MageWorxMultiFees\Block\Totals\Creditmemo;


use Magento\Tax\Model\Config as MagentoTaxConfig;

class Fee extends \MageWorx\MultiFees\Block\Totals\Creditmemo\Fee
{
	protected function getExpandedFeeTotals(array $feesAsArray, $reversOrder = true)
    {
        $totals                = [];
        $displayTaxInSalesMode = $this->helperData->getTaxInSales();

        foreach ($feesAsArray as $feeId => $feeItem) {
        	$title = $feeItem['title'];
        	if (isset($feeItem['options'])) {
        		foreach ($feeItem['options'] as $option) {
        			if (isset($option['percent']) && $option['percent'] > 0) {
        				$title .= ' - ';
        				$title .= (float) $option['percent'];
        				$title .= '%';
        				break;
        			}
        		}
        	}
            switch ($displayTaxInSalesMode) {
                case MagentoTaxConfig::DISPLAY_TYPE_INCLUDING_TAX:
                    $totals[] = [
                        'code'       => 'mageworx_fee_incl_tax_' . $feeId,
                        'value'      => $feeItem['price'],
                        'base_value' => $feeItem['base_price'],
                        'label'      => $title
                    ];
                    break;
                case MagentoTaxConfig::DISPLAY_TYPE_BOTH:
                    $amountWithTax     = (float)$feeItem['price'] - (float)$feeItem['tax'];
                    $amountBaseWithTax = (float)$feeItem['base_price'] - (float)$feeItem['base_tax'];
                    $totals[]          = [
                        'code'       => 'mageworx_fee_' . $feeId,
                        'value'      => $amountWithTax,
                        'base_value' => $amountBaseWithTax,
                        'label'      => $title . ' (' . __('Excl. Tax') . '):'
                    ];
                    $totals[]          = [
                        'code'       => 'mageworx_fee_incl_tax_' . $feeId,
                        'value'      => $feeItem['price'],
                        'base_value' => $feeItem['base_price'],
                        'label'      => $title . ' (' . __('Incl. Tax') . '):'
                    ];
                    break;
                case MagentoTaxConfig::DISPLAY_TYPE_EXCLUDING_TAX:
                    $amountWithTax     = (float)$feeItem['price'] - (float)$feeItem['tax'];
                    $amountBaseWithTax = (float)$feeItem['base_price'] - (float)$feeItem['base_tax'];
                    $totals[]          = [
                        'code'       => 'mageworx_fee_' . $feeId,
                        'value'      => $amountWithTax,
                        'base_value' => $amountBaseWithTax,
                        'label'      => $title
                    ];
                    break;
                default:
                    break;
            }
        }

        return $reversOrder ? array_reverse($totals) : $totals;
    }
}
