<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Plugin\Klarna;


class AddFeeAsSurcharge
{
    /**
     * @param \Klarna\Core\Model\Api\Builder $subject
     * @param array $orderLines
     * @return array
     */
    public function afterGetOrderLines($subject, $orderLines = [])
    {
        $quote = $subject->getObject();
        if (!$quote) {
            return [$orderLines];
        }

        $address = $quote->getIsVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        if (!$address) {
            return [$orderLines];
        }

        $feeAmount = (float)$address->getBaseMageworxFeeAmount();
        $feeTax    = (float)$address->getBaseMageworxFeeTaxAmount();
        $overallTaxPercent = $feeAmount != 0 ? $feeTax / $feeAmount * 100 : 0;

        // Regular fees
        $feeOrderLine = [
            'type'             => 'surcharge',
            'reference'        => 'multifees',
            'name'             => 'multifees',
            'quantity'         => 1,
            'unit_price'       => $this->toApiFloat($feeAmount),
            'tax_rate'         => $overallTaxPercent,
            'total_amount'     => $this->toApiFloat($feeAmount),
            'total_tax_amount' => $this->toApiFloat($feeTax),
        ];

        $orderLines[] = $feeOrderLine;

        // Product fees
        $productFeeAmount = (float)$address->getBaseMageworxProductFeeAmount();
        $productFeeTax    = (float)$address->getBaseMageworxProductFeeTaxAmount();
        $productFeeOverallTaxPercent = $productFeeAmount != 0 ? $productFeeTax / $productFeeAmount * 100 : 0;

        $productFeeOrderLine = [
            'type'             => 'surcharge',
            'reference'        => 'product_fees',
            'name'             => 'product_fees',
            'quantity'         => 1,
            'unit_price'       => $this->toApiFloat($productFeeAmount),
            'tax_rate'         => $productFeeOverallTaxPercent,
            'total_amount'     => $this->toApiFloat($productFeeAmount),
            'total_tax_amount' => $this->toApiFloat($productFeeTax),
        ];

        $orderLines[] = $productFeeOrderLine;

        return $orderLines;
    }

    /**
     * Prepare float for API call
     *
     * @param float $float
     *
     * @return int
     */
    private function toApiFloat(float $float)
    {
        return round($float * 100);
    }
}
