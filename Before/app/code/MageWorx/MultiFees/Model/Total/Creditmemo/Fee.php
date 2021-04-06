<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Class Fee
 */
class Fee extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this|void
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        if ((float)$order->getMageworxFeeAmount() > 0 && (float)$order->getMageworxFeeRefunded(
            ) < (float)$order->getMageworxFeeInvoiced()) {
            $creditmemo->setMageworxFeeAmount(
                (float)$order->getMageworxFeeInvoiced() - (float)$order->getMageworxFeeRefunded()
            );
            $creditmemo->setBaseMageworxFeeAmount(
                (float)$order->getBaseMageworxFeeInvoiced() - (float)$order->getBaseMageworxFeeRefunded()
            );
            $creditmemo->setMageworxFeeTaxAmount($order->getMageworxFeeTaxAmount());
            $creditmemo->setBaseMageworxFeeTaxAmount($order->getBaseMageworxFeeTaxAmount());
            $creditmemo->setMageworxFeeDetails($order->getMageworxFeeDetails());

            if (!$creditmemo->isLast()) { //if is last creditmemo then fee tax already included in grand total
                $creditmemo->setBaseGrandTotal(
                    (float)$creditmemo->getBaseGrandTotal() +
                    (float)$creditmemo->getBaseMageworxFeeAmount()
                );
                $creditmemo->setGrandTotal(
                    (float)$creditmemo->getGrandTotal() +
                    (float)$creditmemo->getMageworxFeeAmount()
                );
            } else {
                $creditmemo->setBaseGrandTotal(
                    (float)$creditmemo->getBaseGrandTotal() +
                    (float)$creditmemo->getBaseMageworxFeeAmount() -
                    (float)$creditmemo->getBaseMageworxFeeTaxAmount()
                );
                $creditmemo->setGrandTotal(
                    (float)$creditmemo->getGrandTotal() +
                    (float)$creditmemo->getMageworxFeeAmount() -
                    (float)$creditmemo->getMageworxFeeTaxAmount()
                );
            }
            if ($order->getBaseTaxAmount() > $creditmemo->getBaseTaxAmount()) {
                $creditmemo->setBaseTaxAmount(
                    (float)$creditmemo->getBaseTaxAmount() + (float)$order->getBaseMageworxFeeTaxAmount()
                );
            }
            if ($order->getTaxAmount() > $creditmemo->getTaxAmount()) {
                $creditmemo->setTaxAmount(
                    (float)$creditmemo->getTaxAmount() + (float)$order->getMageworxFeeTaxAmount()
                );
            }
        } else {
            $creditmemo->setMageworxFeeAmount(0);
            $creditmemo->setBaseMageworxFeeAmount(0);
            $creditmemo->setMageworxFeeTaxAmount(0);
            $creditmemo->setBaseMageworxFeeTaxAmount(0);
            $creditmemo->setMageworxFeeDetails('');
        }

        return $this;
    }
}