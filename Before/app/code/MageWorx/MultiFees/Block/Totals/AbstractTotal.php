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
 * Abstract totals class for the different sources: order, invoice, creditmemo
 *
 */
abstract class AbstractTotal extends \Magento\Sales\Block\Order\Totals
{
    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    protected $helperData;

    /**
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \MageWorx\MultiFees\Helper\Data $helperFee
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \MageWorx\MultiFees\Helper\Data $helperFee,
        array $data = []
    ) {
        $this->helperData = $helperFee;
        parent::__construct($context, $registry, $data);
    }

    /**
     * Add MageWorx Fee Amount to Order
     */
    public function initTotals()
    {
        $totalsBlock = $this->getParentTotalsBlock();
        $source      = $this->getRealSource();
        $storeId     = $source->getStoreId();

        $multifeesAmount = $this->getFeeAmount($source);
        if (!$multifeesAmount) {
            return;
        }

        $feeDetails = $this->getFeeDetails($source);

        if ($feeDetails && $this->helperData->expandFeeDetailsInPdf($storeId)) {
            $feesAsArray = $this->helperData->unserializeValue($feeDetails);

            $totals      = $this->getExpandedFeeTotals($feesAsArray);
        } else {
            $totals = $this->getRegularFeeTotals($source);
        }

        $before = 'grand_total';
        foreach ($totals as $total) {
            $totalsBlock->addTotalBefore(
                new \Magento\Framework\DataObject(
                    $total
                ),
                $before
            );

            $before = $total['code'];
        }
    }

    /**
     * @return \Magento\Sales\Model\AbstractModel;
     */
    abstract protected function getRealSource();

    /**
     * @param \Magento\Sales\Model\AbstractModel $source
     * @return mixed
     */
    protected function getFeeDetails($source)
    {
        return $source->getMageworxFeeDetails();
    }

    /**
     * @param \Magento\Sales\Model\AbstractModel $source
     * @return mixed
     */
    protected function getFeeAmount($source)
    {
        return (float)$source->getMageworxFeeAmount();
    }

    /**
     * @return \Magento\Sales\Block\Order\Totals|\Magento\Sales\Block\Order\Invoice\Totals|\Magento\Sales\Block\Order\Creditmemo\Totals
     */
    protected function getParentTotalsBlock()
    {
        /** @var \Magento\Sales\Block\Order\Totals|\Magento\Sales\Block\Order\Invoice\Totals|\Magento\Sales\Block\Order\Creditmemo\Totals $totalsBlock */
        $totalsBlock = $this->getParentBlock();

        return $totalsBlock;
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
        $displayTaxInSalesMode = $this->helperData->getTaxInSales();

        foreach ($feesAsArray as $feeId => $feeItem) {
            switch ($displayTaxInSalesMode) {
                case MagentoTaxConfig::DISPLAY_TYPE_INCLUDING_TAX:
                    $totals[] = [
                        'code'       => 'mageworx_fee_incl_tax_' . $feeId,
                        'value'      => $feeItem['price'],
                        'base_value' => $feeItem['base_price'],
                        'label'      => $feeItem['title']
                    ];
                    break;
                case MagentoTaxConfig::DISPLAY_TYPE_BOTH:
                    $amountWithTax     = (float)$feeItem['price'] - (float)$feeItem['tax'];
                    $amountBaseWithTax = (float)$feeItem['base_price'] - (float)$feeItem['base_tax'];
                    $totals[]          = [
                        'code'       => 'mageworx_fee_' . $feeId,
                        'value'      => $amountWithTax,
                        'base_value' => $amountBaseWithTax,
                        'label'      => $feeItem['title'] . ' (' . __('Excl. Tax') . '):'
                    ];
                    $totals[]          = [
                        'code'       => 'mageworx_fee_incl_tax_' . $feeId,
                        'value'      => $feeItem['price'],
                        'base_value' => $feeItem['base_price'],
                        'label'      => $feeItem['title'] . ' (' . __('Incl. Tax') . '):'
                    ];
                    break;
                case MagentoTaxConfig::DISPLAY_TYPE_EXCLUDING_TAX:
                    $amountWithTax     = (float)$feeItem['price'] - (float)$feeItem['tax'];
                    $amountBaseWithTax = (float)$feeItem['base_price'] - (float)$feeItem['base_tax'];
                    $totals[]          = [
                        'code'       => 'mageworx_fee_' . $feeId,
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

        $multifeesAmount        = (float)$source->getMageworxFeeAmount();
        $multifeesBaseAmount    = (float)$source->getBaseMageworxFeeAmount();
        $multifeesTaxAmount     = (float)$source->getMageworxFeeTaxAmount();
        $multifeesBaseTaxAmount = (float)$source->getBaseMageworxFeeTaxAmount();

        switch ($displayTaxInSalesMode) {
            case MagentoTaxConfig::DISPLAY_TYPE_INCLUDING_TAX:
                $totals[] = [
                    'code'       => 'mageworx_fee_incl_tax',
                    'value'      => $multifeesAmount,
                    'base_value' => $multifeesBaseAmount,
                    'label'      => __('Additional Fees')
                ];
                break;
            case MagentoTaxConfig::DISPLAY_TYPE_BOTH:
                $totals[]          = [
                    'code'       => 'mageworx_fee_incl_tax',
                    'value'      => $multifeesAmount,
                    'base_value' => $multifeesBaseAmount,
                    'label'      => __('Additional Fees (Incl. Tax)')
                ];
                $amountWithTax     = $multifeesAmount - $multifeesTaxAmount;
                $amountBaseWithTax = $multifeesBaseAmount - $multifeesBaseTaxAmount;
                $totals[]          = [
                    'code'       => 'mageworx_fee_amount',
                    'value'      => $amountWithTax,
                    'base_value' => $amountBaseWithTax,
                    'label'      => __('Additional Fees (Excl. Tax')
                ];
                break;
            case MagentoTaxConfig::DISPLAY_TYPE_EXCLUDING_TAX:
                $amountWithTax     = $multifeesAmount - $multifeesTaxAmount;
                $amountBaseWithTax = $multifeesBaseAmount - $multifeesBaseTaxAmount;
                $totals[]          = [
                    'code'       => 'mageworx_fee_amount',
                    'value'      => $amountWithTax,
                    'base_value' => $amountBaseWithTax,
                    'label'      => __('Additional Fees')
                ];
                break;
            default:
                break;
        }

        return $totals;
    }
}