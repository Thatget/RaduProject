<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Total\Invoice;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Class ProductFee
 */
class ProductFee extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();

        if ((float)$order->getMageworxProductFeeAmount() > 0 &&
            (float)$order->getMageworxProductFeeInvoiced() < ((float)$order->getMageworxProductFeeAmount(
                ) - (float)$order->getMageworxProductFeeCancelled())
        ) {
            $invoice->setMageworxProductFeeAmount(
                (float)$order->getMageworxProductFeeAmount() -
                (float)$order->getMageworxProductFeeInvoiced() -
                (float)$order->getMageworxProductFeeCancelled()
            );
            $invoice->setBaseMageworxProductFeeAmount(
                (float)$order->getBaseMageworxProductFeeAmount() -
                (float)$order->getBaseMageworxProductFeeInvoiced() -
                (float)$order->getBaseMageworxProductFeeCancelled()
            );
            $invoice->setMageworxProductFeeTaxAmount((float)$order->getMageworxProductFeeTaxAmount());
            $invoice->setBaseMageworxProductFeeTaxAmount((float)$order->getBaseMageworxProductFeeTaxAmount());
            $invoice->setMageworxProductFeeDetails($order->getMageworxProductFeeDetails());

            if (!$invoice->isLast()) { //if is last invoice then fee tax already included in grand total
                $invoice->setGrandTotal(
                    (float)$invoice->getGrandTotal() +
                    (float)$invoice->getMageworxProductFeeAmount()
                );
                $invoice->setBaseGrandTotal(
                    (float)$invoice->getBaseGrandTotal() +
                    (float)$invoice->getBaseMageworxProductFeeAmount()
                );
            } else {
                $invoice->setGrandTotal(
                    (float)$invoice->getGrandTotal() +
                    (float)$invoice->getMageworxProductFeeAmount() -
                    (float)$invoice->getMageworxProductFeeTaxAmount()
                );
                $invoice->setBaseGrandTotal(
                    (float)$invoice->getBaseGrandTotal() +
                    (float)$invoice->getBaseMageworxProductFeeAmount() -
                    (float)$invoice->getBaseMageworxProductFeeTaxAmount()
                );
            }

            if ($order->getBaseTaxAmount() > $invoice->getBaseTaxAmount()) {
                $invoice->setBaseTaxAmount(
                    (float)$invoice->getBaseTaxAmount() + (float)$order->getBaseMageworxProductFeeTaxAmount()
                );
            }
            if ($order->getTaxAmount() > $invoice->getTaxAmount()) {
                $invoice->setTaxAmount(
                    (float)$invoice->getTaxAmount() + (float)$order->getMageworxProductFeeTaxAmount()
                );
            }
        } else {
            $invoice->setMageworxProductFeeAmount(0);
            $invoice->setBaseMageworxProductFeeAmount(0);
            $invoice->setMageworxProductFeeTaxAmount(0);
            $invoice->setBaseMageworProductFeeTaxAmount(0);
            $invoice->setMageworxProductFeeDetails('');
        }

        return $this;
    }
}
