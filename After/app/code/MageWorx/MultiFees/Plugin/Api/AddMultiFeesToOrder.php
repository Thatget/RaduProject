<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Plugin\Api;

use Magento\Sales\Api\Data\OrderExtension;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;

class AddMultiFeesToOrder
{
    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;

    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    private $helperData;


    /**
     * AddMultiFeesToOrder constructor.
     *
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     */
    public function __construct(
        OrderExtensionFactory $orderExtensionFactory,
        \MageWorx\MultiFees\Helper\Data $helperData
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->helperData            = $helperData;
    }

    /**
     * Set Multi Fees Data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        /** @var OrderExtension $extensionAttributes */
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->orderExtensionFactory->create();
        }

        // Regular fees
        $extensionAttributes->setMageworxFeeAmount($order->getMageworxFeeAmount());
        $extensionAttributes->setBaseMageworxFeeAmount($order->getBaseMageworxFeeAmount());
        $extensionAttributes->setMageworxFeeTaxAmount($order->getMageworxFeeTaxAmount());
        $extensionAttributes->setBaseMageworxFeeTaxAmount($order->getBaseMageworxFeeTaxAmount());
        $extensionAttributes->setMageworxFeeInvoiced($order->getMageworxFeeInvoiced());
        $extensionAttributes->setBaseMageworxFeeInvoiced($order->getBaseMageworxFeeInvoiced());
        $extensionAttributes->setMageworxFeeRefunded($order->getMageworxFeeRefunded());
        $extensionAttributes->setBaseMageworxFeeRefunded($order->getBaseMageworxFeeRefunded());
        $extensionAttributes->setMageworxFeeCancelled($order->getMageworxFeeCancelled());
        $extensionAttributes->setBaseMageworxFeeCancelled($order->getBaseMageworxFeeCancelled());

        // Product fee
        $extensionAttributes->setMageworxProductFeeAmount($order->getMageworxProductFeeAmount());
        $extensionAttributes->setBaseMageworxProductFeeAmount($order->getBaseMageworxProductFeeAmount());
        $extensionAttributes->setMageworxProductFeeTaxAmount($order->getMageworxProductFeeTaxAmount());
        $extensionAttributes->setBaseMageworxProductFeeTaxAmount($order->getBaseMageworxProductFeeTaxAmount());
        $extensionAttributes->setMageworxProductFeeInvoiced($order->getMageworxProductFeeInvoiced());
        $extensionAttributes->setBaseMageworxProductFeeInvoiced($order->getBaseMageworxProductFeeInvoiced());
        $extensionAttributes->setMageworxProductFeeRefunded($order->getMageworxProductFeeRefunded());
        $extensionAttributes->setBaseMageworxProductFeeRefunded($order->getBaseMageworxProductFeeRefunded());
        $extensionAttributes->setMageworxProductFeeCancelled($order->getMageworxProductFeeCancelled());
        $extensionAttributes->setBaseMageworxProductFeeCancelled($order->getBaseMageworxProductFeeCancelled());

        // Regular fees details
        if ($order->getMageworxFeeDetails()) {
            $feeDetails = $this->helperData->unserializeValue($order->getMageworxFeeDetails());
        } else {
            $feeDetails = [];
        }
        $extensionAttributes->setMageworxFeeDetails(json_encode($feeDetails));

        // Product fee details
        if ($order->getMageworxProductFeeDetails()) {
            $feeDetails = $this->helperData->unserializeValue($order->getMageworxProductFeeDetails());
        } else {
            $feeDetails = [];
        }
        $extensionAttributes->setMageworxProductFeeDetails(json_encode($feeDetails));

        // Save
        $order->setExtensionAttributes($extensionAttributes);

        return $order;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $orderSearchResult
     * @return OrderSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $orderSearchResult
    ) {
        /** @var OrderInterface $entity */
        foreach ($orderSearchResult->getItems() as $order) {
            $this->afterGet($subject, $order);
        }

        return $orderSearchResult;
    }
}
