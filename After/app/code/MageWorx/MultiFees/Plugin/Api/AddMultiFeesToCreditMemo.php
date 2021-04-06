<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Plugin\Api;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoSearchResultInterface;

class AddMultiFeesToCreditMemo
{
    /**
     * @var CreditmemoExtensionFactory
     */
    private $creditmemoExtensionFactory;

    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    private $helperData;

    /**
     * AddMultiFeesToCreditMemo constructor.
     *
     * @param CreditmemoExtensionFactory $creditmemoExtensionFactory
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     */
    public function __construct(
        CreditmemoExtensionFactory $creditmemoExtensionFactory,
        \MageWorx\MultiFees\Helper\Data $helperData
    ) {
        $this->creditmemoExtensionFactory = $creditmemoExtensionFactory;
        $this->helperData                 = $helperData;
    }

    /**
     * Set Multi Fees Data
     *
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $creditMemo
     * @return CreditmemoInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        CreditmemoRepositoryInterface $subject,
        CreditmemoInterface $creditMemo
    ) {
        /** @var \Magento\Sales\Api\Data\CreditmemoExtension $extensionAttributes */
        $extensionAttributes = $creditMemo->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->creditmemoExtensionFactory->create();
        }

        // Regular fees
        $extensionAttributes->setMageworxFeeAmount($creditMemo->getMageworxFeeAmount());
        $extensionAttributes->setBaseMageworxFeeAmount($creditMemo->getBaseMageworxFeeAmount());
        $extensionAttributes->setMageworxFeeTaxAmount($creditMemo->getMageworxFeeTaxAmount());
        $extensionAttributes->setBaseMageworxFeeTaxAmount($creditMemo->getBaseMageworxFeeTaxAmount());

        if ($creditMemo->getMageworxFeeDetails()) {
            $feeDetails = $this->helperData->unserializeValue($creditMemo->getMageworxFeeDetails());
        } else {
            $feeDetails = [];
        }
        $extensionAttributes->setMageworxFeeDetails(json_encode($feeDetails));

        // Product fee
        $extensionAttributes->setMageworxProductFeeAmount($creditMemo->getMageworxProductFeeAmount());
        $extensionAttributes->setBaseMageworxProductFeeAmount($creditMemo->getBaseMageworxProductFeeAmount());
        $extensionAttributes->setMageworxProductFeeTaxAmount($creditMemo->getMageworxProductFeeTaxAmount());
        $extensionAttributes->setBaseMageworxProductFeeTaxAmount($creditMemo->getBaseMageworxProductFeeTaxAmount());

        if ($creditMemo->getMageworxProductFeeDetails()) {
            $feeDetails = $this->helperData->unserializeValue($creditMemo->getMageworxProductFeeDetails());
        } else {
            $feeDetails = [];
        }
        $extensionAttributes->setMageworxProductFeeDetails(json_encode($feeDetails));

        // Save
        $creditMemo->setExtensionAttributes($extensionAttributes);

        return $creditMemo;
    }

    /**
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoSearchResultInterface $creditMemoSearchResult
     * @return CreditmemoSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        CreditmemoRepositoryInterface $subject,
        CreditmemoSearchResultInterface $creditMemoSearchResult
    ) {
        /** @var CreditmemoInterface $entity */
        foreach ($creditMemoSearchResult->getItems() as $creditMemo) {
            $this->afterGet($subject, $creditMemo);
        }

        return $creditMemoSearchResult;
    }
}
