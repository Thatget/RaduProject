<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddFeeToOrderObserver implements ObserverInterface
{

    /**
     * @var \MageWorx\MultiFees\Helper\ProductFee
     */
    protected $helperFee;

    /**
     * AddFeeStatistics constructor.
     *
     * @param \MageWorx\MultiFees\Helper\ProductFee $helperFee
     */
    public function __construct(
        \MageWorx\MultiFees\Helper\ProductFee $helperFee
    ) {
        $this->helperFee = $helperFee;
    }

    /**
     * @param EventObserver $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        $order = $observer->getOrder();
        $quote = $observer->getQuote();

        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }

        if ($address->getMageworxFeeAmount()) {
            $order->setMageworxFeeAmount($address->getMageworxFeeAmount());
            $order->setBaseMageworxFeeAmount($address->getBaseMageworxFeeAmount());
            $order->setMageworxFeeDetails($address->getMageworxFeeDetails());
            $order->setMageworxFeeTaxAmount($address->getMageworxFeeTaxAmount());
            $order->setBaseMageworxFeeTaxAmount($address->getBaseMageworxFeeTaxAmount());
        }

        if ($address->getMageworxProductFeeAmount()) {
            $order->setMageworxProductFeeAmount($address->getMageworxProductFeeAmount());
            $order->setBaseMageworxProductFeeAmount($address->getBaseMageworxProductFeeAmount());
            $order->setMageworxProductFeeDetails($address->getMageworxProductFeeDetails());
            $order->setMageworxProductFeeTaxAmount($address->getMageworxProductFeeTaxAmount());
            $order->setBaseMageworxProductFeeTaxAmount($address->getBaseMageworxProductFeeTaxAmount());
        }

        return $this;
    }
}
