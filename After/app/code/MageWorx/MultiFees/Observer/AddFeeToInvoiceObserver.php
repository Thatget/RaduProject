<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddFeeToInvoiceObserver implements ObserverInterface
{
    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        if ($invoice->getBaseMageworxFeeAmount() > 0) {
            $order = $invoice->getOrder();
            $order->setBaseMageworxFeeInvoiced(
                (float)$order->getBaseMageworxFeeInvoiced() + (float)$invoice->getBaseMageworxFeeAmount()
            );
            $order->setMageworxFeeInvoiced(
                (float)$order->getMageworxFeeInvoiced() + (float)$invoice->getMageworxFeeAmount()
            );
        }

        if ($invoice->getBaseMageworxProductFeeAmount() > 0) {
            $order = $invoice->getOrder();
            $order->setBaseMageworxProductFeeInvoiced(
                (float)$order->getBaseMageworxProductFeeInvoiced() + (float)$invoice->getBaseMageworxProductFeeAmount()
            );
            $order->setMageworxProductFeeInvoiced(
                (float)$order->getMageworxProductFeeInvoiced() + (float)$invoice->getMageworxProductFeeAmount()
            );
        }

        return $this;
    }
}
