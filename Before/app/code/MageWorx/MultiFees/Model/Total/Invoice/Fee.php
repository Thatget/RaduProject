<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Total\Invoice;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Class Fee
 */
class Fee extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {

        $order = $invoice->getOrder();

        if ((float)$order->getMageworxFeeAmount() > 0 &&
            (float)$order->getMageworxFeeInvoiced() < ((float)$order->getMageworxFeeAmount(
                ) - (float)$order->getMageworxFeeCancelled())
        ) {
            $invoice->setMageworxFeeAmount(
                (float)$order->getMageworxFeeAmount() -
                (float)$order->getMageworxFeeInvoiced() -
                (float)$order->getMageworxFeeCancelled()
            );
            $invoice->setBaseMageworxFeeAmount(
                (float)$order->getBaseMageworxFeeAmount() -
                (float)$order->getBaseMageworxFeeInvoiced() -
                (float)$order->getBaseMageworxFeeCancelled()
            );
            $invoice->setMageworxFeeTaxAmount($order->getMageworxFeeTaxAmount());
            $invoice->setBaseMageworxFeeTaxAmount($order->getBaseMageworxFeeTaxAmount());
            $invoice->setMageworxFeeDetails($order->getMageworxFeeDetails());

            if (!$invoice->isLast()) { //if is last invoice then fee tax already included in grand total
                $invoice->setGrandTotal(
                    (float)$invoice->getGrandTotal() +
                    (float)$invoice->getMageworxFeeAmount()
                );
                $invoice->setBaseGrandTotal(
                    (float)$invoice->getBaseGrandTotal() +
                    (float)$invoice->getBaseMageworxFeeAmount()
                );
            } else {
                $invoice->setGrandTotal(
                    (float)$invoice->getGrandTotal() +
                    (float)$invoice->getMageworxFeeAmount() -
                    (float)$invoice->getMageworxFeeTaxAmount()
                );
                $invoice->setBaseGrandTotal(
                    (float)$invoice->getBaseGrandTotal() +
                    (float)$invoice->getBaseMageworxFeeAmount() -
                    (float)$invoice->getBaseMageworxFeeTaxAmount()
                );
            }

            if ($order->getBaseTaxAmount() > $invoice->getBaseTaxAmount()) {
                $invoice->setBaseTaxAmount(
                    (float)$invoice->getBaseTaxAmount() + (float)$order->getBaseMageworxFeeTaxAmount()
                );
            }
            if ($order->getTaxAmount() > $invoice->getTaxAmount()) {
                $invoice->setTaxAmount((float)$invoice->getTaxAmount() + (float)$order->getMageworxFeeTaxAmount());
            }
        } else {
            $invoice->setMageworxFeeAmount(0);
            $invoice->setBaseMageworxFeeAmount(0);
            $invoice->setMageworxFeeTaxAmount(0);
            $invoice->setBaseMageworxFeeTaxAmount(0);
            $invoice->setMageworxFeeDetails('');
        }

        return $this;
    }
}
