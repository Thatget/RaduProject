<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Plugin\Api;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\Data\InvoiceSearchResultInterface;

class AddMultiFeesToInvoice
{
    /**
     * @var InvoiceExtensionFactory
     */
    private $invoiceExtensionFactory;

    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    private $helperData;

    /**
     * AddMultiFeesToInvoice constructor.
     *
     * @param InvoiceExtensionFactory $invoiceExtensionFactory
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     */
    public function __construct(
        InvoiceExtensionFactory $invoiceExtensionFactory,
        \MageWorx\MultiFees\Helper\Data $helperData
    ) {
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
        $this->helperData              = $helperData;
    }

    /**
     * Set Multi Fees Data
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceInterface $invoice
     * @return InvoiceInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        InvoiceRepositoryInterface $subject,
        InvoiceInterface $invoice
    ) {
        /** @var \Magento\Sales\Api\Data\InvoiceExtension $extensionAttributes */
        $extensionAttributes = $invoice->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->invoiceExtensionFactory->create();
        }

        // Regular fees
        $extensionAttributes->setMageworxFeeAmount($invoice->getMageworxFeeAmount());
        $extensionAttributes->setBaseMageworxFeeAmount($invoice->getBaseMageworxFeeAmount());
        $extensionAttributes->setMageworxFeeTaxAmount($invoice->getMageworxFeeTaxAmount());
        $extensionAttributes->setBaseMageworxFeeTaxAmount($invoice->getBaseMageworxFeeTaxAmount());

        if ($invoice->getMageworxFeeDetails()) {
            $feeDetails = $this->helperData->unserializeValue($invoice->getMageworxFeeDetails());
        } else {
            $feeDetails = [];
        }
        $extensionAttributes->setMageworxFeeDetails(json_encode($feeDetails));

        // Product fee
        $extensionAttributes->setMageworxProductFeeAmount($invoice->getMageworxProductFeeAmount());
        $extensionAttributes->setBaseMageworxProductFeeAmount($invoice->getBaseMageworxProductFeeAmount());
        $extensionAttributes->setMageworxProductFeeTaxAmount($invoice->getMageworxProductFeeTaxAmount());
        $extensionAttributes->setBaseMageworxProductFeeTaxAmount($invoice->getBaseMageworxProductFeeTaxAmount());

        if ($invoice->getMageworxProductFeeDetails()) {
            $feeDetails = $this->helperData->unserializeValue($invoice->getMageworxProductFeeDetails());
        } else {
            $feeDetails = [];
        }
        $extensionAttributes->setMageworxProductFeeDetails(json_encode($feeDetails));

        // Save
        $invoice->setExtensionAttributes($extensionAttributes);

        return $invoice;
    }

    /**
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceSearchResultInterface $invoiceSearchResult
     * @return InvoiceSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        InvoiceRepositoryInterface $subject,
        InvoiceSearchResultInterface $invoiceSearchResult
    ) {
        /** @var InvoiceInterface $entity */
        foreach ($invoiceSearchResult->getItems() as $invoice) {
            $this->afterGet($subject, $invoice);
        }

        return $invoiceSearchResult;
    }
}
