<?php

namespace Bss\MageWorxMultiFees\Model\Total\Pdf;

use Magento\Framework\Exception\LocalizedException;
use MageWorx\MultiFees\Api\Data\FeeInterface;

class Fee extends \MageWorx\MultiFees\Model\Total\Pdf\Fee
{
	private $feeHelper;

	public function __construct(
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory $ordersFactory,
        \MageWorx\MultiFees\Helper\Data $feeHelper,
        array $data = []
    ) {
        $this->feeHelper = $feeHelper;
        parent::__construct(
        	$taxHelper,
        	$taxCalculation,
        	$ordersFactory,
        	$feeHelper,
        	$data
        );
    }

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
            $feesAsArray = json_decode($feeDetails, true);
            $totals      = $this->getGroupedFeeTotals($feesAsArray);
        } else {
            // Just one total
            $totals = $this->getRegularFeesTotal();
        }

        return $totals;
    }

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

            foreach ($groupedFees as $feeId => $feeItem) {
            	$title = __('Additional Fees');
                switch ($displayTaxInSalesMode) {
                    case \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX:
                        $amount   = $this->formatAmountValue($feeItem['price']);
                        $totals[] = [
                            'amount'    => $amount,
                            'label'     => $this->makeLabelFromTitle($title) . ':',
                            'font_size' => $fontSize
                        ];
                        break;
                    case \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH:
                        $amount    = $this->formatAmountValue($feeItem['price']);
                        $totals[]  = [
                            'amount'    => $amount,
                            'label'     => $this->makeLabelFromTitle($title) . ' (' . __(
                                    'Incl. Tax'
                                ) . '):',
                            'font_size' => $fontSize
                        ];
                        $amountTax = $this->formatAmountValue((float)$feeItem['price'] - (float)$feeItem['tax']);
                        $totals[]  = [
                            'amount'    => $amountTax,
                            'label'     => $this->makeLabelFromTitle($title) . ' (' . __(
                                    'Excl. Tax'
                                ) . '):',
                            'font_size' => $fontSize
                        ];
                        break;
                    case \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX:
                        $amountTax = $this->formatAmountValue((float)$feeItem['price'] - (float)$feeItem['tax']);
                        $totals[]  = [
                            'amount'    => $amountTax,
                            'label'     => $this->makeLabelFromTitle($title) . ':',
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

    private function formatAmountValue($amount)
    {
        $amount = $this->getOrder()->formatPriceTxt($amount);
        if ($this->getAmountPrefix()) {
            $amount = $this->getAmountPrefix() . $amount;
        }

        return $amount;
    }
}
