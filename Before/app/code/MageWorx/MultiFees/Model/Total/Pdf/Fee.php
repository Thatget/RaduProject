<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Total\Pdf;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Pdf\Total\DefaultTotal;
use MageWorx\MultiFees\Api\Data\FeeInterface;

/**
 * Class Fee
 */
class Fee extends DefaultTotal
{
    /**
     * Describes a fee with unsupported or unknown type
     */
    const FEE_TYPE_OTHER = 'other';

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
        return $this->getSource()->getMageworxFeeAmount();
    }

    /**
     * @return float
     */
    public function getAmountWithTax()
    {
        $source = $this->getSource();
        $amount = (float)$source->getMageworxFeeAmount() - (float)$source->getMageworxFeeTaxAmount();

        return $amount;
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
     * @throws LocalizedException
     */
    public function getTotalsForDisplay()
    {
        /**
         * @var \Magento\Sales\Model\Order\Invoice $source
         */
        $source     = $this->getSource();
        $storeId    = $source->getStoreId();
        $feeDetails = $source->getMageworxFeeDetails();
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
     * Groups existing fees by its type.
     *
     * Supported types in the result array:
     *  - cart
     *  - shipping
     *  - payment
     *  - other
     *
     * @param array $feesAsArray
     * @return array
     * @throws LocalizedException
     */
    private function groupFeesByType(array $feesAsArray)
    {
        $feesByType = [
            FeeInterface::CART_TYPE     => [],
            FeeInterface::SHIPPING_TYPE => [],
            FeeInterface::PAYMENT_TYPE  => [],
            static::FEE_TYPE_OTHER      => []
        ];

        foreach ($feesAsArray as $feeId => $feeItem) {
            if (empty($feeItem[FeeInterface::TYPE])) {
                throw new LocalizedException(__('Empty fee type for the fee with id %1', $feeId));
            }
            switch ($feeItem[FeeInterface::TYPE]) {
                case FeeInterface::CART_TYPE:
                    $feesByType[FeeInterface::CART_TYPE][$feeId] = $feeItem;
                    break;
                case FeeInterface::SHIPPING_TYPE:
                    $feesByType[FeeInterface::SHIPPING_TYPE][$feeId] = $feeItem;
                    break;
                case FeeInterface::PAYMENT_TYPE:
                    $feesByType[FeeInterface::PAYMENT_TYPE][$feeId] = $feeItem;
                    break;
                default:
                    $feesByType[static::FEE_TYPE_OTHER][$feeId] = $feeItem;
                    break;
            }
        }

        return $feesByType;
    }

    /**
     * Get a label for specified fees type.
     *
     * @param string|int $type
     * @return \Magento\Framework\Phrase
     */
    private function getLabelForFeeType($type)
    {
        switch ($type) {
            case FeeInterface::CART_TYPE:
                $label = __('Cart Fees:');
                break;
            case FeeInterface::SHIPPING_TYPE:
                $label = __('Shipping Fees:');
                break;
            case FeeInterface::PAYMENT_TYPE:
                $label = __('Payment Fees:');
                break;
            default:
                $label = __('Other Fees:');
                break;
        }

        return $label;
    }

    /**
     * Make the totals array with fees grouped by its type with\without tax.
     *
     * @param array $feesAsArray
     * @return array
     * @throws LocalizedException
     */
    private function getGroupedFeeTotals(array $feesAsArray)
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

        $feesByType = $this->groupFeesByType($feesAsArray);
        foreach ($feesByType as $type => $groupedFees) {
            if (empty($groupedFees)) {
                // Do not display empty fees group in the totals block
                continue;
            }
            $label    = $this->getLabelForFeeType($type);
            $totals[] = [
                'amount'    => '',
                'label'     => $label,
                'font_size' => $fontSize
            ];
            foreach ($groupedFees as $feeId => $feeItem) {
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
}
