<?php

namespace Bss\MageWorxMultiFees\Helper;

use Magento\Tax\Model\Config as TaxConfig;

class Price extends \MageWorx\MultiFees\Helper\Price
{
	public function getOptionFormatPrice($option, $fee, $onlyValue = false)
    {
        $price      = $option->getPrice();
        $taxClassId = $fee->getTaxClassId();

        $quote      = $this->helperFee->getQuote();
        $address    = $this->getSalesAddress($quote);
        $validItems = $this->helperFee->validateItems($quote, $fee);
        $store = $quote->getStore();

        if (!count($validItems)) {
            $price = 0;
        }

        $percent = 0;
        if ($option->getPriceType() == \MageWorx\MultiFees\Model\AbstractFee::PERCENT_ACTION) {
            $percent = $price;

            $appliedTotals       = is_array($fee->getAppliedTotals()) ?
                $fee->getAppliedTotals() :
                explode(',', $fee->getAppliedTotals());
            $baseMageworxFeeLeft = $this->helperFee->getBaseFeeLeft(
                $address,
                $appliedTotals,
                $fee,
                $validItems
            );

            $price   = ($baseMageworxFeeLeft > 0 && $percent > 0) ? ($baseMageworxFeeLeft * $percent) / 100 : 0;
            $price = $this->priceCurrency->convert($price, $store);
        	return $this->priceCurrency->format($price, false);
        }
        if (!$fee->getIsOnetime()) {
            $price = $this->getQtyMultiplicationPrice($price, $fee, $validItems);
        }

        
        $price = $this->priceCurrency->convert($price, $store); // base price - to store price

        if ($onlyValue) {
            return $price;
        }

        // tax_calculation_includes_tax
        if ($this->helperData->isTaxCalculationIncludesTax()) {
            $priceInclTax = $price;
            $price        = $this->getPriceExcludeTax($price, $quote, $fee->getTaxClassId(), $address);
        } else {
            $priceInclTax = $price + $this->getTaxPrice($price, $quote, $taxClassId, $address);
        }

        $taxInBlock = $this->helperData->getTaxInBlock();

        if ($taxInBlock == TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX) {
            $formatPrice = $percent ? $percent : $this->priceCurrency->format($price, false);

            return $formatPrice;
        }

        if ($taxInBlock == TaxConfig::DISPLAY_TYPE_INCLUDING_TAX) {
            $priceInclTax = $this->priceCurrency->format($priceInclTax, false);
            if ($percent) {
                $priceInclTax = $percent . ' (' . $priceInclTax . ')';
            }

            return $priceInclTax;
        }

        if ($taxInBlock == TaxConfig::DISPLAY_TYPE_BOTH) {
            $formatPrice  = $this->priceCurrency->format($price, false);
            $priceInclTax = $this->priceCurrency->format($priceInclTax, false);
            if ($percent) {
                return $percent;
            }

            return $formatPrice . ' (' . __('Incl. Tax %1', $priceInclTax) . ')';
        }
    }
}
