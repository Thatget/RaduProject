<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Total\Pdf;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Pdf\Total\DefaultTotal;

/**
 * Class ProductFee
 */
class ProductFee extends DefaultTotal
{
    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    private $feeHelper;

    /**
     * Fee constructor.
     *
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param \Magento\Tax\Model\Calculation $taxCalculation
     * @param \Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory $ordersFactory
     * @param \MageWorx\MultiFees\Helper\Data $feeHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory $ordersFactory,
        \MageWorx\MultiFees\Helper\Data $feeHelper,
        array $data = []
    ) {
        $this->feeHelper = $feeHelper;
        parent::__construct($taxHelper, $taxCalculation, $ordersFactory, $data);
    }

    /**
     * Retrieve Total amount from source
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->getSource()->getMageworxProductFeeAmount();
    }

    /**
     * @return float
     */
    public function getAmountWithTax()
    {
        $source = $this->getSource();
        $amount = (float)$source->getMageworxProductFeeAmount() - (float)$source->getMageworxProductFeeTaxAmount();

        return $amount;
    }

    /**
     * Formats amount price
     *
     * @param int|string|float $amount
     * @return string
     */
    private function formatAmountValue($amount)
    {
        $amount = $this->getOrder()->formatPriceTxt($amount);
        if ($this->getAmountPrefix()) {
            $amount = $this->getAmountPrefix() . $amount;
        }

        return $amount;
    }

    /**
     * Create total's label from its title
     *
     * @param string $title
     * @return string
     */
    private function makeLabelFromTitle($title)
    {
        $title = __($title);
        if ($this->getTitleSourceField()) {
            $label = $title . ' (' . $this->getTitleDescription() . ')';
        } else {
            $label = $title;
        }

        return $label;
    }

    /**
     * Get array of arrays with totals information for display in PDF
     * array(
     *  $index => array(
     *      'amount'   => $amount,
     *      'label'    => $label,
     *      'font_size'=> $font_size
     *  )
     * )
     *
     * @return array
     */
    public function getTotalsForDisplay()
    {
        /**
         * @var \Magento\Sales\Model\Order\Invoice $source
         */
        $source     = $this->getSource();
        $storeId    = $source->getStoreId();
        $feeDetails = $source->getMageworxProductFeeDetails();
        if ($feeDetails && $this->feeHelper->expandFeeDetailsInPdf($storeId)) {
            // Fees totals grouped by type
            $feesAsArray = $this->feeHelper->unserializeValue($feeDetails);
            $totals      = $this->getGroupedFeeTotals($feesAsArray);
        } else {
            // Just one total
            $totals = $this->getRegularFeesTotal();
        }

        return $totals;
    }

    /**
     * Make a regular fee total with\without tax.
     *
     * @return array
     */
    private function getRegularFeesTotal()
    {
        /**
         * One of the:
         * \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX
         * \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX
         * \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH
         */
        $displayTaxInSalesMode = $this->feeHelper->getTaxInSales();
        $fontSize              = $this->getFontSize() ? $this->getFontSize() : 7;

        // Array with all totals which will be rendered in pdf
        $totals = [];

        $label = $this->makeLabelFromTitle($this->getTitle());
        switch ($displayTaxInSalesMode) {
            case \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX:
                $amount    = $this->formatAmountValue($this->getAmount());
                $totals[0] = [
                    'amount'    => $amount,
                    'label'     => $label . ':',
                    'font_size' => $fontSize
                ];
                break;
            case \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH:
                $amount    = $this->formatAmountValue($this->getAmount());
                $totals[0] = [
                    'amount'    => $amount,
                    'label'     => $label . ' (' . __('Incl. Tax') . '):',
                    'font_size' => $fontSize
                ];
                $amountTax = $this->formatAmountValue($this->getAmountWithTax());
                $totals[1] = [
                    'amount'    => $amountTax,
                    'label'     => $label . ' (' . __('Excl. Tax') . '):',
                    'font_size' => $fontSize
                ];
                break;
            case \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX:
                $amountTax = $this->formatAmountValue($this->getAmountWithTax());
                $totals[0] = [
                    'amount'    => $amountTax,
                    'label'     => $label . ':',
                    'font_size' => $fontSize
                ];
                break;
            default:
                break;
        }

        return $totals;
    }

    /**
     * Make the totals array with fees grouped by its type with\without tax.
     *
     * @param array $groupedFees
     * @return array
     */
    private function getGroupedFeeTotals(array $groupedFees)
    {
        /**
         * One of the:
         * \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX
         * \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX
         * \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH
         */
        $displayTaxInSalesMode = $this->feeHelper->getTaxInSales();
        $fontSize              = $this->getFontSize() ? $this->getFontSize() : 7;

        // Array with all totals which will be rendered in pdf
        $totals = [];

        $label    = __('Product Fees:');
        $totals[] = [
            'amount'    => '',
            'label'     => $label,
            'font_size' => $fontSize
        ];
        foreach ($groupedFees as $itemId => $groupedFee) {
            foreach ($groupedFee as $feeId => $feeItem) {
                switch ($displayTaxInSalesMode) {
                    case \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX:
                        $amount   = $this->formatAmountValue($feeItem['price']);
                        $totals[] = [
                            'amount'    => $amount,
                            'label'     => $this->makeLabelFromTitle($feeItem['title']) . ':',
                            'font_size' => $fontSize
                        ];
                        break;
                    case \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH:
                        $amount    = $this->formatAmountValue($feeItem['price']);
                        $totals[]  = [
                            'amount'    => $amount,
                            'label'     => $this->makeLabelFromTitle($feeItem['title']) . ' (' . __(
                                    'Incl. Tax'
                                ) . '):',
                            'font_size' => $fontSize
                        ];
                        $amountTax = $this->formatAmountValue((float)$feeItem['price'] - (float)$feeItem['tax']);
                        $totals[]  = [
                            'amount'    => $amountTax,
                            'label'     => $this->makeLabelFromTitle($feeItem['title']) . ' (' . __(
                                    'Excl. Tax'
                                ) . '):',
                            'font_size' => $fontSize
                        ];
                        break;
                    case \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX:
                        $amountTax = $this->formatAmountValue((float)$feeItem['price'] - (float)$feeItem['tax']);
                        $totals[]  = [
                            'amount'    => $amountTax,
                            'label'     => $this->makeLabelFromTitle($feeItem['title']) . ':',
                            'font_size' => $fontSize
                        ];
                        break;
                    default:
                        break;
                }
            }
        }

        if (!empty($totals)) {
            $totals[] = [
                'amount'    => '',
                'label'     => '',
                'font_size' => $fontSize
            ];
        }

        return $totals;
    }
}
