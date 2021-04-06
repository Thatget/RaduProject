<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddFeeToCreditmemoObserver implements ObserverInterface
{
    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        if ($creditmemo->getBaseMageworxFeeAmount() > 0) {
            $order = $creditmemo->getOrder();
            $order->setBaseMageworxFeeRefunded(
                (float)$order->getBaseMageworxFeeRefunded() + (float)$creditmemo->getBaseMageworxFeeAmount()
            );
            $order->setMageworxFeeRefunded(
                (float)$order->getMageworxFeeRefunded() + (float)$creditmemo->getMageworxFeeAmount()
            );
        }

        if ($creditmemo->getBaseMageworxProductFeeAmount() > 0) {
            $order = $creditmemo->getOrder();
            $order->setBaseMageworxProductFeeRefunded(
                (float)$order->getBaseMageworxProductFeeRefunded() + (float)$creditmemo->getBaseMageworxProductFeeAmount()
            );
            $order->setMageworxProductFeeRefunded(
                (float)$order->getMageworxProductFeeRefunded() + (float)$creditmemo->getMageworxProductFeeAmount()
            );
        }

        return $this;
    }
}
