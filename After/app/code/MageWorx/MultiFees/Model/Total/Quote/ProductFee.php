<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Total\Quote;

use Magento\Tax\Model\Config as TaxConfig;
use MageWorx\MultiFees\Api\FeeCollectionManagerInterface;

class ProductFee extends AbstractFee
{
    /**
     * ProductFee constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     * @param \MageWorx\MultiFees\Helper\Fee $helperFee
     * @param \MageWorx\MultiFees\Helper\Price $helperPrice
     * @param \MageWorx\MultiFees\Helper\ProductFee $helperProductFee
     * @param \MageWorx\MultiFees\Helper\ProductFeePrice $helperProductFeePrice
     * @param \Magento\Tax\Helper\Data $taxHelperData
     * @param FeeCollectionManagerInterface $feeCollectionManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \MageWorx\MultiFees\Helper\Data $helperData,
        \MageWorx\MultiFees\Helper\Fee $helperFee,
        \MageWorx\MultiFees\Helper\Price $helperPrice,
        \MageWorx\MultiFees\Helper\ProductFee $helperProductFee,
        \MageWorx\MultiFees\Helper\ProductFeePrice $helperProductFeePrice,
        \Magento\Tax\Helper\Data $taxHelperData,
        \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feeCollectionManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $eventManager,
            $storeManager,
            $priceCurrency,
            $helperData,
            $helperFee,
            $helperPrice,
            $taxHelperData,
            $feeCollectionManager,
            $logger
        );

        $this->setCode('mageworx_product_fee');
        $this->helperFee   = $helperProductFee;
        $this->helperPrice = $helperProductFeePrice;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param bool $required
     * @param bool $isDefault
     * @return array|\Magento\Framework\DataObject[]|\MageWorx\MultiFees\Api\Data\FeeInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getPossibleFeesItems($quote, $required = false, $isDefault = false)
    {
        return $this->feeCollectionManager
            ->getProductFeeCollection($required, $isDefault)
            ->getItems();
    }

    /**
     * Check is required fees are missed in the current quote
     *
     * @param array $multiFeesInQuote
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkIsRequiredFeesMissed(array $multiFeesInQuote = [], \Magento\Quote\Model\Quote $quote)
    {
        /** @var \MageWorx\MultiFees\Api\Data\FeeInterface[] $possibleFeesItems */
        $possibleFeesItems = $this->collectAllRequiredFeesItems($quote);
        /** @var \MageWorx\MultiFees\Api\Data\FeeInterface[] $missedFeeItems */
        $missedFeeItems = [];
        foreach ($quote->getAllItems() as $item) {
            $validFees = $this->helperFee->validateFeeCollectionByQuoteItem($possibleFeesItems, $item);
            foreach ($validFees as $key => $possibleItem) {
                if (empty($multiFeesInQuote[$possibleItem->getId()][$item->getItemId()])) {
                    $missedFeeItems[$key][$item->getItemId()] = $possibleItem;
                }
            }
        }

        return !empty($missedFeeItems);
    }

    /**
     * Add fee total information to address
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        if (!$total->getMageworxProductFeeDetails() && !$this->isCollected) {
            $quote->collectTotals();
        }

        if ($total->getMageworxProductFeeAmount() && $total->getMageworxProductFeeDetails()) {
            $taxMode = $this->helperData->getTaxInCart();

            if (in_array((int)$taxMode, [TaxConfig::DISPLAY_TYPE_BOTH, TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX])) {

                $applied = $total->getMageworxProductFeeDetails();

                if (is_string($applied)) {
                    $applied = $this->helperData->unserializeValue($applied);
                }

                $applied = $this->convertFeeDetailsForTax($applied);

                return [
                    'code'      => $this->getCode(),
                    'title'     => __('Additional Product Fees'),
                    'value'     => $total->getMageworxProductFeeAmount() - $total->getMageworxProductFeeTaxAmount(),
                    'full_info' => $applied,
                ];
            }
        }

        return null;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        /** @var \Magento\Quote\Model\Quote\Address $address */
        $address = $shippingAssignment->getShipping()->getAddress();

        if ($this->out($shippingAssignment)) {
            return $this;
        }
        //don't collect fees while currency switched
        if ($quote->getQuoteCurrencyCode() != $this->storeManager->getStore()->getCurrentCurrencyCode()) {
            return $this;
        }

        if ($address->getPostcode() || $address->getCountryId() || $address->getRegionId()) {
            $this->helperFee->saveAddressDataToSession($address);
        }
        if (!$address->getPostcode() || !$address->getCountryId() || !$address->getRegionId()) {
            $address = $this->helperFee->getAddressDataFromSession($address);
        }

        // Get data about all fees from the session, in the case of sending the form there are already updated fees
        $feesData = $this->getFeesData($address->getId());
        $feesData = array_filter($feesData, 'is_array');

        // add default fees
        $missedRequiredFees = $this->checkIsRequiredFeesMissed($feesData, $quote);
        if (is_null($feesData) || !empty($missedRequiredFees)) {
            // Adding the fees automatically if there are missed required fees
            // I think here it is necessary to add only the missing fees and not all in a row!
            $feesData = $this->autoAddFeesByParams($quote, $address, $feesData);
        }

        if (empty($feesData)) {
            return $this;
        }

        /** @var \MageWorx\MultiFees\Api\Data\FeeInterface[] $possibleFees */
        $possibleFees = $this->getAllPossibleFees($quote);
        $store        = $quote->getStore();

        $baseMageworxFeeAmount    = 0;
        $baseMageworxFeeTaxAmount = 0;
        $validQuoteItemIds = $this->helperFee->getValidQuoteItemIds($quote);

        foreach ($feesData as $feeId => $quoteItemData) {
            foreach ($quoteItemData as $quoteItemId => $data) {
                if (array_search($quoteItemId, $validQuoteItemIds) === false) {
                    unset($feesData[$feeId][$quoteItemId]); // @protection: old quote item data
                    continue;
                }

                if (!isset($data['options'])) {
                    unset($feesData[$feeId][$quoteItemId]); // @protection: fee without options
                    continue;
                }

                if (empty($possibleFees[$feeId])) {
                    unset($feesData[$feeId][$quoteItemId]); // @protection: remove invalid fees
                    continue;
                }

                $allValidItems = $this->helperFee->validateItems($quote, $possibleFees[$feeId]);
                if (empty($allValidItems)) {
                    unset($feesData[$feeId][$quoteItemId]); // @protection: remove invalid fees
                    continue;
                }
                $validItems = [];
                foreach ($allValidItems as $item) {
                    if ($item->getItemId() == $quoteItemId) {
                        $validItems = [$item];
                        break;
                    }
                }

                $appliedTotals       = is_array($data[\MageWorx\MultiFees\Api\Data\FeeInterface::APPLIED_TOTALS]) ?
                    $data[\MageWorx\MultiFees\Api\Data\FeeInterface::APPLIED_TOTALS] :
                    explode(',', $data[\MageWorx\MultiFees\Api\Data\FeeInterface::APPLIED_TOTALS]);
                $taxClassId = $data['tax_class_id'];

                $feePrice = 0;
                $feeTax   = 0;
                foreach ($data['options'] as $optionId => $value) {
                    /**
                     * @see \MageWorx\MultiFees\Helper\Price::getOptionFormatPrice();
                     */

                    if (isset($value['percent'])) {
                        $baseMageworxFeeLeft = $this->helperFee->getBaseFeeLeft(
                            $total,
                            $appliedTotals,
                            $possibleFees[$feeId],
                            $validItems
                        );
                        $opBasePrice = ($baseMageworxFeeLeft * $value['percent']) / 100;

                        if ($possibleFees[$feeId]->getMinAmount() > $opBasePrice) {
                            $opBasePrice = $possibleFees[$feeId]->getMinAmount();
                        }
                    } else {
                        $opBasePrice = count($validItems) ? $value['base_price'] : 0;
                    }

                    if (!$possibleFees[$feeId]->getIsOnetime()) {
                        $opBasePrice = $this->helperPrice->getQtyMultiplicationPrice(
                            $opBasePrice,
                            $possibleFees[$feeId],
                            $validItems
                        );
                    }

                    $opPrice = $this->priceCurrency->convert($opBasePrice, $store);

                    if ($this->helperData->isTaxCalculationIncludesTax()) {
                        $opTax     = $opPrice - $this->helperPrice->getPriceExcludeTax(
                                $opPrice,
                                $quote,
                                $taxClassId,
                                $address
                            );
                        $opBaseTax = $opBasePrice - $this->helperPrice->getPriceExcludeTax(
                                $opBasePrice,
                                $quote,
                                $taxClassId,
                                $address
                            );
                    } else {
                        // add tax
                        $opTax     = $this->helperPrice->getTaxPrice($opPrice, $quote, $taxClassId, $address);
                        $opBaseTax = $this->helperPrice->getTaxPrice($opBasePrice, $quote, $taxClassId, $address);

                        $opPrice     += $opTax;
                        $opBasePrice += $opBaseTax;
                    }

                    // round price
                    extract($this->massRound(compact($opPrice, $opBasePrice, $opTax, $opBaseTax)));

                    //$opPrice, $opBasePrice = inclTax
                    $feesData[$feeId][$quoteItemId]['options'][$optionId]['price']      = $opPrice;
                    $feesData[$feeId][$quoteItemId]['options'][$optionId]['base_price'] = $opBasePrice;
                    $feesData[$feeId][$quoteItemId]['options'][$optionId]['tax']        = $opTax;
                    $feesData[$feeId][$quoteItemId]['options'][$optionId]['base_tax']   = $opBaseTax;

                    $feeTax   += $opBaseTax;
                    $feePrice += $opBasePrice;
                }

                $feesData[$feeId][$quoteItemId]['base_price'] = $feePrice;
                $feesData[$feeId][$quoteItemId]['price']      = $this->priceCurrency->convert($feePrice, $store);
                $feesData[$feeId][$quoteItemId]['base_tax']   = $feeTax;
                $feesData[$feeId][$quoteItemId]['tax']        = $this->priceCurrency->convert($feeTax, $store);

                $baseMageworxFeeAmount    += $feePrice;
                $baseMageworxFeeTaxAmount += $feeTax;
            }
        }

        $mageworxFeeAmount     = $this->priceCurrency->convertAndRound($baseMageworxFeeAmount, $store);
        $baseMageworxFeeAmount = $this->priceCurrency->round($baseMageworxFeeAmount);

        $mageworxFeeTaxAmount     = $this->priceCurrency->convertAndRound($baseMageworxFeeTaxAmount, $store);
        $baseMageworxFeeTaxAmount = $this->priceCurrency->round($baseMageworxFeeTaxAmount);

        $this->addPricesToAddress($total, $address, $mageworxFeeAmount, $mageworxFeeTaxAmount);
        $this->addBasePricesToAddress($total, $address, $baseMageworxFeeAmount, $baseMageworxFeeTaxAmount);
        $this->addFeesDetailsToAddress($total, $address, $feesData);

        $this->addFeesDetailsToQuote($quote, $feesData);

        $this->isCollected = true;

        return $this;
    }

    /**
     * Retrieve fees and corresponding options array
     *
     * @param null $quote
     * @param null $address
     * @param array $feesPost
     * @return array
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function autoAddFeesByParams($quote = null, $address = null, $feesPost = [])
    {
        if (empty($feesPost)) {
            $feesPost = $this->getFeesData();
            $feesPost = array_filter($feesPost, 'is_array');
        }

        // @uncompleted
        // @TODO add this feature later, it is cool!
        // As I understood it will add the custom message visible for the customer when a fee was applied.
        $feeMessages = null;

        $this->feeCollectionManager
            ->clean()
            ->setQuote($quote)
            ->setAddress($address);

        /** @var \MageWorx\MultiFees\Api\Data\FeeInterface[] $fees */
        $fees = $this->getPossibleFeesItems($quote, true, true);

        if (!count($fees)) {
            return null;
        }

        foreach ($quote->getAllItems() as $item) {
            $validFees = $this->helperFee->validateFeeCollectionByQuoteItem($fees, $item);
            foreach ($validFees as $fee) {
                // Do not add already existent fee for item
                if (!empty($feesPost[$fee->getId()][$item->getItemId()])) {
                    continue;
                }

                // Do not add fee without options
                $feeOptions = $fee->getOptions();
                if (!$feeOptions) {
                    continue;
                }

                // Add default data from the model to the fee data-array, needed later during the totals collecting
                $feesPost[$fee->getId()][$item->getItemId()] = $fee->getData();

                // Process options, find default
                /** @var \MageWorx\MultiFees\Model\Option $option */
                foreach ($feeOptions as $option) {
                    if (!$option->getIsDefault()) {
                        continue;
                    }

                    $opValue = [];
                    $opValue['title'] = $option->getTitle();

                    if ($option->getPriceType() == 'percent') {
                        $opValue['percent'] = $option->getPrice();
                    } else {
                        $opValue['base_price'] = $option->getPrice();
                    }
                    $feesPost[$fee->getId()][$item->getItemId()]['options'][$option->getId()] = $opValue;

                    // @uncompleted
                    // @TODO add this feature later, it is cool!
                    // As I understood it will add the custom message visible for the customer when a fee was applied.
                    if ($feeMessages) {
                        foreach ($feeMessages as $feeMessage) {
                            if ($fee->getId() == $feeMessage->getFeeId()) {
                                $feesPost[$fee->getId()][$item->getItemId()]['message'] = $feeMessage->getMessage();
                            }
                        }
                    }
                }
            }
        }

        // Add fees if data is not empty
        if ($feesPost) {
            $this->helperFee->addFeesToQuote($feesPost, $quote->getStoreId(), false);
        }

        return $feesPost;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param double $mageworxFeeAmount
     * @param double $mageworxFeeTaxAmount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addPricesToAddress($total, $address, $mageworxFeeAmount, $mageworxFeeTaxAmount)
    {
        $total->setMageworxProductFeeAmount($mageworxFeeAmount);
        $total->setMageworxProductFeeTaxAmount($mageworxFeeTaxAmount);
        $total->setTotalAmount('mageworx_product_fee', $mageworxFeeAmount);

        $address->setMageworxProductFeeAmount($mageworxFeeAmount);
        $address->setMageworxProductFeeTaxAmount($mageworxFeeTaxAmount);
        $address->setTotalAmount('mageworx_product_fee', $mageworxFeeAmount);

        $address->setTaxAmount($address->getTaxAmount() + $mageworxFeeTaxAmount);

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param float $baseMageworxFeeAmount
     * @param float $baseMageworxFeeTaxAmount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addBasePricesToAddress($total, $address, $baseMageworxFeeAmount, $baseMageworxFeeTaxAmount)
    {
        $total->setBaseMageworxProductFeeAmount($baseMageworxFeeAmount);
        $total->setBaseMageworxProductFeeTaxAmount($baseMageworxFeeTaxAmount);
        $total->setBaseTotalAmount('mageworx_product_fee', $baseMageworxFeeAmount);

        $address->setBaseMageworxProductFeeAmount($baseMageworxFeeAmount);
        $address->setBaseMageworxProductFeeTaxAmount($baseMageworxFeeTaxAmount);
        $address->setBaseTotalAmount('mageworx_product_fee', $baseMageworxFeeAmount);

        $address->setBaseTaxAmount($address->getBaseTaxAmount() + $baseMageworxFeeTaxAmount);

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param array $feesData
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addFeesDetailsToAddress($total, $address, $feesData)
    {
        $address->setMageworxProductFeeDetails($this->helperData->serializeValue($feesData));
        $total->setMageworxProductFeeDetails($this->helperData->serializeValue($feesData));

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $feesData
     * @return $this
     */
    protected function addFeesDetailsToQuote($quote, $feesData)
    {
        $quote->setMageworxProductFeeDetails($this->helperData->serializeValue($feesData));

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return $this
     */
    protected function resetAddress($address)
    {
        $address->setMageworxProductFeeAmount(0);
        $address->setBaseMageworxProductFeeAmount(0);
        $address->setMageworxProductFeeDetails(null);

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    protected function out($shippingAssignment)
    {
        if (!$this->helperData->isEnable()) {
            return true;
        }

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return true;
        }

        return false;
    }
}
