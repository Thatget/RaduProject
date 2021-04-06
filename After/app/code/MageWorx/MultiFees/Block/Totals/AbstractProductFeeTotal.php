<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Totals;

use Magento\Tax\Model\Config as MagentoTaxConfig;
use Magento\Sales\Model\AbstractModel as AbstractSalesModel;

/**
 * Class AbstractTotal
 *
 * Abstract product fee totals class for the different sources: order, invoice, creditmemo
 *
 */
abstract class AbstractProductFeeTotal extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\AbstractModel $source
     * @return mixed
     */
    protected function getFeeDetails($source)
    {
        return $source->getMageworxProductFeeDetails();
    }

    /**
     * @param \Magento\Sales\Model\AbstractModel $source
     * @return mixed
     */
    protected function getFeeAmount($source)
    {
        return (float)$source->getMageworxProductFeeAmount();
    }

    /**
     * Get regular fee totals.
     *
     * @param AbstractSalesModel $source
     * @return array
     */
    protected function getRegularFeeTotals(AbstractSalesModel $source)
    {
        $totals                = [];
        $displayTaxInSalesMode = $this->helperData->getTaxInSales();

        $multifeesAmount        = (float)$source->getMageworxProductFeeAmount();
        $multifeesBaseAmount    = (float)$source->getBaseMageworxProductFeeAmount();
        $multifeesTaxAmount     = (float)$source->getMageworxProductFeeTaxAmount();
        $multifeesBaseTaxAmount = (float)$source->getBaseMageworxProductFeeTaxAmount();

        switch ($displayTaxInSalesMode) {
            case MagentoTaxConfig::DISPLAY_TYPE_INCLUDING_TAX:
                $totals[] = [
                    'code'       => 'mageworx_product_fee_incl_tax',
                    'value'      => $multifeesAmount,
                    'base_value' => $multifeesBaseAmount,
                    'label'      => __('Additional Product Fees')
                ];
                break;
            case MagentoTaxConfig::DISPLAY_TYPE_BOTH:
                $totals[]          = [
                    'code'       => 'mageworx_product_fee_incl_tax',
                    'value'      => $multifeesAmount,
                    'base_value' => $multifeesBaseAmount,
                    'label'      => __('Additional Product Fees (Incl. Tax)')
                ];
                $amountWithTax     = $multifeesAmount - $multifeesTaxAmount;
                $amountBaseWithTax = $multifeesBaseAmount - $multifeesBaseTaxAmount;
                $totals[]          = [
                    'code'       => 'mageworx_product_fee_amount',
                    'value'      => $amountWithTax,
                    'base_value' => $amountBaseWithTax,
                    'label'      => __('Additional Product Fees (Excl. Tax')
                ];
                break;
            case MagentoTaxConfig::DISPLAY_TYPE_EXCLUDING_TAX:
                $amountWithTax     = $multifeesAmount - $multifeesTaxAmount;
                $amountBaseWithTax = $multifeesBaseAmount - $multifeesBaseTaxAmount;
                $totals[]          = [
                    'code'       => 'mageworx_product_fee_amount',
                    'value'      => $amountWithTax,
                    'base_value' => $amountBaseWithTax,
                    'label'      => __('Additional Product Fees')
                ];
                break;
            default:
                break;
        }

        return $totals;
    }

    /**
     * Collect all fees
     * 1 fee == 1 row
     *
     * @param array $feesAsArray
     * @param bool $reversOrder
     * @return array
     */
    protected function getExpandedFeeTotals(array $feesAsArray, $reversOrder = true)
    {
        $totals                = [];
        $preparedFees          = [];
        $displayTaxInSalesMode = $this->helperData->getTaxInSales();

        //calculate product fee by type from every product
        foreach ($feesAsArray as $feeId => $feeData) {
            $preparedFees[$feeId]               = [];
            $preparedFees[$feeId]['price']      = 0;
            $preparedFees[$feeId]['base_price'] = 0;
            $preparedFees[$feeId]['tax']        = 0;
            $preparedFees[$feeId]['base_tax']   = 0;
            foreach ($feeData as $productId => $feeItem) {
                $preparedFees[$feeId]['title']      = $feeItem['title'];
                $preparedFees[$feeId]['price']      += $feeItem['price'];
                $preparedFees[$feeId]['base_price'] += $feeItem['base_price'];
                $preparedFees[$feeId]['tax']        += $feeItem['tax'];
                $preparedFees[$feeId]['base_tax']   += $feeItem['base_tax'];
            }
        }

        foreach ($preparedFees as $feeId => $feeItem) {
            switch ($displayTaxInSalesMode) {
                case MagentoTaxConfig::DISPLAY_TYPE_INCLUDING_TAX:
                    $totals[] = [
                        'code'       => 'mageworx_product_fee_incl_tax_' . $feeId,
                        'value'      => $feeItem['price'],
                        'base_value' => $feeItem['base_price'],
                        'label'      => $feeItem['title']
                    ];
                    break;
                case MagentoTaxConfig::DISPLAY_TYPE_BOTH:
                    $amountWithTax     = (float)$feeItem['price'] - (float)$feeItem['tax'];
                    $amountBaseWithTax = (float)$feeItem['base_price'] - (float)$feeItem['base_tax'];
                    $totals[]          = [
                        'code'       => 'mageworx_product_fee_' . $feeId,
                        'value'      => $amountWithTax,
                        'base_value' => $amountBaseWithTax,
                        'label'      => $feeItem['title'] . ' (' . __('Excl. Tax') . '):'
                    ];
                    $totals[]          = [
                        'code'       => 'mageworx_product_fee_incl_tax_' . $feeId,
                        'value'      => $feeItem['price'],
                        'base_value' => $feeItem['base_price'],
                        'label'      => $feeItem['title'] . ' (' . __('Incl. Tax') . '):'
                    ];
                    break;
                case MagentoTaxConfig::DISPLAY_TYPE_EXCLUDING_TAX:
                    $amountWithTax     = (float)$feeItem['price'] - (float)$feeItem['tax'];
                    $amountBaseWithTax = (float)$feeItem['base_price'] - (float)$feeItem['base_tax'];
                    $totals[]          = [
                        'code'       => 'mageworx_product_fee_' . $feeId,
                        'value'      => $amountWithTax,
                        'base_value' => $amountBaseWithTax,
                        'label'      => $feeItem['title']
                    ];
                    break;
                default:
                    break;
            }
        }

        return $reversOrder ? array_reverse($totals) : $totals;
    }
}