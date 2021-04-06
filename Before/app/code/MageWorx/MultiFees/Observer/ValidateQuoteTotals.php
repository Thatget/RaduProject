<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 *
 * @event sales_quote_collect_totals_after
 */
class ValidateQuoteTotals implements ObserverInterface
{
    /**
     * @var \MageWorx\MultiFees\Helper\Fee
     */
    protected $helperFee;

    /**
     * @var \MageWorx\MultiFees\Helper\ProductFee
     */
    protected $helperProductFee;

    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    protected $helperData;

    /**
     * Request object
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \MageWorx\MultiFees\Api\FeeCollectionManagerInterfaceFactory
     */
    protected $collectionManagerInterfaceFactory;

    /**
     * ValidateQuoteTotals constructor.
     *
     * @param \MageWorx\MultiFees\Helper\Fee $helperFee
     * @param \MageWorx\MultiFees\Helper\ProductFee $helperProductFee
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \MageWorx\MultiFees\Api\FeeCollectionManagerInterfaceFactory $collectionManagerInterfaceFactory
     */
    public function __construct(
        \MageWorx\MultiFees\Helper\Fee $helperFee,
        \MageWorx\MultiFees\Helper\ProductFee $helperProductFee,
        \MageWorx\MultiFees\Helper\Data $helperData,
        \Magento\Framework\App\RequestInterface $request,
        \MageWorx\MultiFees\Api\FeeCollectionManagerInterfaceFactory $collectionManagerInterfaceFactory
    ) {
        $this->helperFee                         = $helperFee;
        $this->helperProductFee                  = $helperProductFee;
        $this->helperData                        = $helperData;
        $this->request                           = $request;
        $this->collectionManagerInterfaceFactory = $collectionManagerInterfaceFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // check required fees
        if (!$this->helperData->isEnable()) {
            return $this;
        }
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote    = $observer->getEvent()->getQuote();
        $session  = $this->helperFee->getCurrentSession();

        $session->setMultifeesValidationFailed(false);

        $this->validateCartFees($session, $quote);
        $this->validateProductFees($session, $quote);

        return $this;
    }

    /**
     * @param \Magento\Backend\Model\Session\Quote|\Magento\Checkout\Model\Session $session
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateProductFees($session, $quote)
    {
        $feesData = $this->helperProductFee->getQuoteDetailsMultifees();
        /** @var \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feesManager */
        $feesManager = $this->collectionManagerInterfaceFactory->create();

        // Validate product fees
        $requiredProductFees = $feesManager->setQuote($quote)->getRequiredProductFees();
        if (count($requiredProductFees)) {
            foreach ($quote->getAllItems() as $item) {
                $validCollection = $this->helperProductFee->validateFeeCollectionByQuoteItem($requiredProductFees, $item);
                foreach ($validCollection as $fee) {
                    if (!isset($feesData[$fee->getFeeId()][$item->getItemId()])) {
                        $quote->addErrorInfo(
                            'error',
                            'multifees',
                            \MageWorx\MultiFees\Helper\Fee::ERROR_REQUIRED_CART_FEE_MISS,
                            __('%1 product fee is required', $fee->getTitle())
                        );
                        $session->setMultifeesValidationFailed(true);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param \Magento\Backend\Model\Session\Quote|\Magento\Checkout\Model\Session $session
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateCartFees($session, $quote)
    {
        $feesData = $this->helperFee->getQuoteDetailsMultifees();
        /** @var \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feesManager */
        $feesManager = $this->collectionManagerInterfaceFactory->create();

        // Validate cart fees
        $requiredCartFees = $feesManager->setQuote($quote)->getRequiredCartFees();
        if (count($requiredCartFees)) {
            foreach ($requiredCartFees as $fee) {
                if (!isset($feesData[$fee->getFeeId()])) {
                    $quote->addErrorInfo(
                        'error',
                        'multifees',
                        \MageWorx\MultiFees\Helper\Fee::ERROR_REQUIRED_CART_FEE_MISS,
                        __('%1 cart fee is required', $fee->getTitle())
                    );
                    $session->setMultifeesValidationFailed(true);
                }
            }
        }

        return $this;
    }
}
