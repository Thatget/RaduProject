<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\MultiFees\Api\Data\CartFeeInterface;
use MageWorx\MultiFees\Api\Data\FeeInterface;
use MageWorx\MultiFees\Api\Data\PaymentFeeInterface;
use MageWorx\MultiFees\Api\Data\ShippingFeeInterface;

/**
 * Class AddFeeStatistics
 *
 * @event sales_order_place_after
 */
class AddFeeStatistics implements ObserverInterface
{
    /**
     * @var \MageWorx\MultiFees\Helper\Fee
     */
    protected $helperFee;

    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    protected $helperData;

    /**
     * AddFeeStatistics constructor.
     *
     * @param \MageWorx\MultiFees\Helper\Fee $helperFee
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     */
    public function __construct(
        \MageWorx\MultiFees\Helper\Fee $helperFee,
        \MageWorx\MultiFees\Helper\Data $helperData
    ) {
        $this->helperFee  = $helperFee;
        $this->helperData = $helperData;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order    = $observer->getEvent()->getOrder();
        $feesData = $order->getMageworxFeeDetails();
        $feesData = $feesData ? $this->helperData->unserializeValue($feesData) : [];

        foreach ($feesData as $feeId => $data) {
            if (!isset($data['options'])) {
                continue;
            }

            $repository = $this->helperFee->getSuitableFeeRepositoryByType(
                $data[FeeInterface::TYPE]
            );

            /** @var CartFeeInterface|ShippingFeeInterface|PaymentFeeInterface|FeeInterface $fee */
            $fee = $repository->getById($feeId);

            if ($fee->getId()) {
                $fee->setTotalBaseAmount($fee->getTotalBaseAmount() + floatval($data['base_price']))
                    ->setTotalOrdered($fee->getTotalOrdered() + 1);
                $repository->save($fee);
            }
        }
        $this->clearSessionMageworxFeeDetails();

        return $this;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function clearSessionMageworxFeeDetails()
    {
        $this->helperFee->getCurrentSession()->setMageworxFeeDetails(null);
    }
}