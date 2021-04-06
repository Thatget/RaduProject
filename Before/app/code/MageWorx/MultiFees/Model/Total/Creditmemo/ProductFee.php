<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Class ProductFee
 */
class ProductFee extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this|void
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        if ((float)$order->getMageworxProductFeeAmount() > 0 && (float)$order->getMageworxProductFeeRefunded(
            ) < (float)$order->getMageworxProductFeeInvoiced()) {
            $creditmemo->setMageworxProductFeeAmount(
                (float)$order->getMageworxProductFeeInvoiced() - (float)$order->getMageworxProductFeeRefunded()
            );
            $creditmemo->setBaseMageworxProductFeeAmount(
                (float)$order->getBaseMageworxProductFeeInvoiced() - (float)$order->getBaseMageworxProductFeeRefunded()
            );
            $creditmemo->setMageworxProductFeeTaxAmount($order->getMageworxProductFeeTaxAmount());
            $creditmemo->setBaseMageworxProductFeeTaxAmount($order->getBaseMageworxProductFeeTaxAmount());
            $creditmemo->setMageworxProductFeeDetails($order->getMageworxProductFeeDetails());

            if (!$creditmemo->isLast()) { //if is last creditmemo then fee tax already included in grand total
                $creditmemo->setBaseGrandTotal(
                    (float)$creditmemo->getBaseGrandTotal() +
                    (float)$creditmemo->getBaseMageworxProductFeeAmount()
                );
                $creditmemo->setGrandTotal(
                    (float)$creditmemo->getGrandTotal() +
                    (float)$creditmemo->getMageworxProductFeeAmount()
                );
            } else {
                $creditmemo->setBaseGrandTotal(
                    (float)$creditmemo->getBaseGrandTotal() +
                    (float)$creditmemo->getBaseMageworxProductFeeAmount() -
                    (float)$creditmemo->getBaseMageworxProductFeeTaxAmount()
                );
                $creditmemo->setGrandTotal(
                    (float)$creditmemo->getGrandTotal() +
                    (float)$creditmemo->getMageworxProductFeeAmount() -
                    (float)$creditmemo->getMageworxProductFeeTaxAmount()
                );
            }
            if ($order->getBaseTaxAmount() > $creditmemo->getBaseTaxAmount()) {
                $creditmemo->setBaseTaxAmount(
                    (float)$creditmemo->getBaseTaxAmount() + (float)$order->getBaseMageworxProductFeeTaxAmount()
                );
            }
            if ($order->getTaxAmount() > $creditmemo->getTaxAmount()) {
                $creditmemo->setTaxAmount(
                    (float)$creditmemo->getTaxAmount() + (float)$order->getMageworxProductFeeTaxAmount()
                );
            }
        } else {
            $creditmemo->setMageworxProductFeeAmount(0);
            $creditmemo->setBaseMageworxProductFeeAmount(0);
            $creditmemo->setMageworxProductFeeTaxAmount(0);
            $creditmemo->setBaseMageworxProductFeeTaxAmount(0);
            $creditmemo->setMageworxProductFeeDetails('');
        }

        return $this;
    }
}