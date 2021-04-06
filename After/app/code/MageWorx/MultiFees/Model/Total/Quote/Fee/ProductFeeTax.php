<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Total\Quote\Fee;

use Magento\Tax\Model\Config as TaxConfig;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\TaxClassManagementInterface;
use MageWorx\MultiFees\Api\Data\FeeInterface;

/**
 * Class Tax
 *
 * @package MageWorx\MultiFees\Model\Total\Quote\Fee
 */
class ProductFeeTax extends Tax
{
    /**
     * ProductFeeTax constructor.
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
     * @param \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feeCollectionManager
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository
     * @param \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyInterfaceFactory
     * @param \Magento\Tax\Model\Calculation $calculationTool
     * @param TaxClassManagementInterface $taxClassManagement
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
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository,
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyInterfaceFactory,
        \Magento\Tax\Model\Calculation $calculationTool,
        TaxClassManagementInterface $taxClassManagement,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->setCode('mageworx_product_fee_tax');
        parent::__construct(
            $eventManager,
            $storeManager,
            $priceCurrency,
            $helperData,
            $helperFee,
            $helperPrice,
            $taxHelperData,
            $feeCollectionManager,
            $taxClassRepository,
            $taxClassKeyInterfaceFactory,
            $calculationTool,
            $taxClassManagement,
            $logger
        );
        $this->helperFee = $helperProductFee;
        $this->helperPrice = $helperProductFeePrice;
    }

    /**
     * Collect address fee amount
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        //Add the shipping tax to total tax amount
        $total->addTotalAmount('tax', $total->getMageworxProductFeeTaxAmount());
        $total->addBaseTotalAmount('tax', $total->getBaseMageworxProductFeeTaxAmount());

        /** @var \Magento\Quote\Model\Quote\Address $address */
        $address = $shippingAssignment->getShipping()->getAddress();

        if ($this->out($address, $shippingAssignment)) {
            return $this;
        }
        //don't collect fees while currency switched
        if ($quote->getQuoteCurrencyCode() != $this->storeManager->getStore()->getCurrentCurrencyCode()) {
            return $this;
        }

        if (!$address->getPostcode() || !$address->getCountryId() || !$address->getRegionId()) {
            $address = $this->helperFee->getAddressDataFromSession($address);
        }

        $store = $quote->getStore();
        /** @var \MageWorx\MultiFees\Api\Data\FeeInterface[] $possibleFees */
        $possibleFees = $this->getAllPossibleFees($quote);

        // Get data about all fees from the session, in the case of sending the form there are already updated fees
        $feesData = $this->helperFee->getQuoteDetailsMultifees($address->getId());
        foreach ($feesData as $feeId => $quoteItemData) {
            foreach ($quoteItemData as $quoteItemId => $data) {
                if (!isset($data['options'])) {
                    continue;
                }

                if (empty($possibleFees[$feeId])) {
                    continue;
                }
                $currentPossibleFee = $possibleFees[$feeId];

                $validItems = $this->helperFee->validateItems($quote, $currentPossibleFee);

                if (is_array($data[FeeInterface::APPLIED_TOTALS])) {
                    $appliedTotals = $data[FeeInterface::APPLIED_TOTALS];
                } else {
                    $appliedTotals = explode(',', $data[FeeInterface::APPLIED_TOTALS]);
                }

                $baseMageworxFeeLeft = $this->helperFee->getBaseFeeLeft(
                    $total,
                    $appliedTotals,
                    $currentPossibleFee,
                    $validItems
                );

                $taxClassId = $data['tax_class_id'];

                if (!$taxClassId) {
                    return $this;
                }

                $feePrice = 0;
                $feeTax   = 0;
                foreach ($data['options'] as $optionId => $value) {
                    /**
                     * @see \MageWorx\MultiFees\Helper\Price::getOptionFormatPrice();
                     */
                    if (isset($value['percent'])) {
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
                            $currentPossibleFee,
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

                if ($address->getData('applied_taxes')) {
                    $taxClass = $this->taxClassRepository->get($taxClassId);
                    $taxInfo  = $this->taxClassKeyDataObjectFactory->create()
                                                                   ->setType(TaxClassKeyInterface::TYPE_ID)
                                                                   ->setValue(
                                                                       $taxClass->getClassId()
                                                                   );
                    $taxRateRequest = $this->getAddressRateRequest($quote);
                    $taxRateRequest->setProductClassId(
                        $this->taxClassManagement->getTaxClassId($taxInfo)
                    );
                    $appliedRates = $this->calculationTool->getAppliedRates($taxRateRequest);
                    if (empty($appliedRates[0]['rates'][0]['code'])) {
                        continue;
                    }
                    $rate     = $appliedRates[0]['rates'][0];
                    $rateCode = $rate['code'];

                    $this->saveAppliedFeeTaxes($total, $rateCode, [ $feeId => $feesData[$feeId][$quoteItemId]], $feeId, $rate);
                }
            }
        }

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\Total $total $total
     * @param string $rateCode
     * @param array $feesData
     * @param int $feeId
     * @param array $rate
     * @return $this
     */
    protected function saveAppliedFeeTaxes($total, $rateCode, $feesData, $feeId, $rate)
    {
        $appliedTaxes = $total->getAppliedTaxes();
        if (is_array($appliedTaxes) && !empty($appliedTaxes[$rateCode])) {
            $appliedTaxes[$rateCode]['amount']      += $feesData[$feeId]['tax'];
            $appliedTaxes[$rateCode]['base_amount'] += $feesData[$feeId]['base_tax'];
        } else {
            $appliedTaxes[$rateCode] = [
                'amount'             => $feesData[$feeId]['tax'],
                'base_amount'        => $feesData[$feeId]['base_tax'],
                'percent'            => $rate['percent'],
                'id'                 => $rate['code'],
                'item_id'            => null,
                'item_type'          => 'shipping',
                'associated_item_id' => null,
                'process'            => 1,
                'rates'              => [
                    $rate
                ]
            ];
        }
        $itemAppliedTaxes = $total->getItemsAppliedTaxes();
        $itemAppliedTaxes['mageworx_fee_tax'] = [
            [
                'amount'             => $feesData[$feeId]['tax'],
                'base_amount'        => $feesData[$feeId]['base_tax'],
                'percent'            => $rate['percent'],
                'id'                 => $rate['code'],
                'item_id'            => null,
                'item_type'          => 'mageworx_fee_tax',
                'associated_item_id' => null,
                'rates'              => [
                    $rate
                ]
            ]
        ];

        $total->setAppliedTaxes($appliedTaxes);
        $total->setItemsAppliedTaxes($itemAppliedTaxes);

        return $this;
    }

    /**
     * Get address rate request
     *
     * Request object contain:
     *  country_id (->getCountryId())
     *  region_id (->getRegionId())
     *  postcode (->getPostcode())
     *  customer_class_id (->getCustomerClassId())
     *  store (->getStore())
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Framework\DataObject
     */
    protected function getAddressRateRequest(\Magento\Quote\Model\Quote $quote)
    {
        if (null == $this->addressRateRequest) {
            $this->addressRateRequest = $this->calculationTool->getRateRequest(
                $quote->getShippingAddress(),
                $quote->getBillingAddress(),
                $quote->getCustomerTaxClassId(),
                $quote->getStoreId(),
                $quote->getCustomerId()
            );
        }

        return $this->addressRateRequest;
    }

    /**
     * @param array $values
     * @return array
     */
    protected function massRound(array $values)
    {
        foreach ($values as $key => $value) {
            $values[$key] = $this->priceCurrency->round($value);
        }

        return $values;
    }

    /**
     * Get all possible fees (not only required)
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \MageWorx\MultiFees\Api\Data\FeeInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getAllPossibleFees(\Magento\Quote\Model\Quote $quote)
    {
        $possibleFeesCollection = $this->feeCollectionManager->getProductFeeCollection()->getItems();

        $possibleFees = [];
        foreach ($possibleFeesCollection as $fee) {
            $possibleFees[$fee->getId()] = $fee;
        }

        return $possibleFees;
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
        if ($total->getMageworxProductFeeAmount()) {
            $taxMode = $this->helperData->getTaxInCart();

            if (in_array((int)$taxMode, [TaxConfig::DISPLAY_TYPE_BOTH, TaxConfig::DISPLAY_TYPE_INCLUDING_TAX])) {
                $applied = $total->getMageworxProductFeeDetails();
                if (is_string($applied)) {
                    $applied = $this->helperData->unserializeValue($applied);
                }

                if ($applied) {
                    $result = [
                        'code'      => $this->getCode(),
                        'title'     => __('Additional Product Fees (Incl. Tax)'),
                        'value'     => $total->getMageworxProductFeeAmount(),
                        'full_info' => $applied,
                    ];

                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @return bool
     */
    protected function out($address, $shippingAssignment)
    {
        if (!$this->helperData->isEnable()) {
            return true;
        }

        if ($address->getSubtotal() == 0) {
            return true;
        }

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return true;
        }

        return false;
    }
}
