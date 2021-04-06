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
class Tax extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    protected $mageworxFeeAmount;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    protected $helperData;

    /**
     * @var \MageWorx\MultiFees\Helper\Fee
     */
    protected $helperFee;

    /**
     * @var \MageWorx\MultiFees\Helper\Price
     */
    protected $helperPrice;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxHelperData;

    /**
     * @var bool
     */
    protected $isCollected;

    /**
     * @var \MageWorx\MultiFees\Api\FeeCollectionManagerInterface
     */
    protected $feeCollectionManager;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    protected $taxClassRepository;

    /**
     * @var \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory
     */
    protected $taxClassKeyDataObjectFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $addressRateRequest;

    /**
     * @var \Magento\Tax\Model\Calculation
     */
    protected $calculationTool;

    /**
     * Tax Class Service
     *
     * @var TaxClassManagementInterface
     */
    protected $taxClassManagement;

    /**
     * Fee constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     * @param \MageWorx\MultiFees\Helper\Fee $helperFee
     * @param \MageWorx\MultiFees\Helper\Price $helperPrice
     * @param \Magento\Tax\Helper\Data $taxHelperData
     * @param \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feeCollectionManager
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository
     * @param \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyInterfaceFactory
     * @param \Magento\Tax\Model\Calculation $calculationTool
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \MageWorx\MultiFees\Helper\Data $helperData,
        \MageWorx\MultiFees\Helper\Fee $helperFee,
        \MageWorx\MultiFees\Helper\Price $helperPrice,
        \Magento\Tax\Helper\Data $taxHelperData,
        \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feeCollectionManager,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository,
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyInterfaceFactory,
        \Magento\Tax\Model\Calculation $calculationTool,
        TaxClassManagementInterface $taxClassManagement,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->setCode('mageworx_fee_tax');
        $this->eventManager                 = $eventManager;
        $this->storeManager                 = $storeManager;
        $this->priceCurrency                = $priceCurrency;
        $this->helperData                   = $helperData;
        $this->helperFee                    = $helperFee;
        $this->helperPrice                  = $helperPrice;
        $this->taxHelperData                = $taxHelperData;
        $this->feeCollectionManager         = $feeCollectionManager;
        $this->taxClassRepository           = $taxClassRepository;
        $this->taxClassKeyDataObjectFactory = $taxClassKeyInterfaceFactory;
        $this->calculationTool              = $calculationTool;
        $this->taxClassManagement           = $taxClassManagement;
        $this->logger                       = $logger;
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
        $total->addTotalAmount('tax', $total->getMageworxFeeTaxAmount());
        $total->addBaseTotalAmount('tax', $total->getBaseMageworxFeeTaxAmount());

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
        foreach ($feesData as $feeId => $data) {
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
                $feesData[$feeId]['options'][$optionId]['price']      = $opPrice;
                $feesData[$feeId]['options'][$optionId]['base_price'] = $opBasePrice;
                $feesData[$feeId]['options'][$optionId]['tax']        = $opTax;
                $feesData[$feeId]['options'][$optionId]['base_tax']   = $opBaseTax;

                $feeTax   += $opBaseTax;
                $feePrice += $opBasePrice;
            }

            $feesData[$feeId]['base_price'] = $feePrice;
            $feesData[$feeId]['price']      = $this->priceCurrency->convert($feePrice, $store);
            $feesData[$feeId]['base_tax']   = $feeTax;
            $feesData[$feeId]['tax']        = $this->priceCurrency->convert($feeTax, $store);

            if ($address->getData('applied_taxes')) {
                $taxClass       = $this->taxClassRepository->get($taxClassId);
                $taxInfo        = $this->taxClassKeyDataObjectFactory->create()
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

                // emulation $this->_saveAppliedTaxes($address, $applied, $tax, $baseTax, $rate);
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
            }
        }

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
        /** @var \MageWorx\MultiFees\Api\Data\FeeInterface[] $possibleFeesCollection */
        if ($quote->getIsVirtual()) {
            $requiredCartFees       = $this->feeCollectionManager->getCartFeeCollection()
                                                                 ->getItems();
            $requiredPaymentFees    = $this->feeCollectionManager->getPaymentFeeCollection()
                                                                 ->getItems();
            $possibleFeesCollection = array_merge($requiredCartFees, $requiredPaymentFees);
        } else {
            $requiredCartFees       = $this->feeCollectionManager->getCartFeeCollection()
                                                                 ->getItems();
            $requiredShippingFees   = $this->feeCollectionManager->getShippingFeeCollection()
                                                                 ->getItems();
            $requiredPaymentFees    = $this->feeCollectionManager->getPaymentFeeCollection()
                                                                 ->getItems();
            $possibleFeesCollection = array_merge($requiredCartFees, $requiredShippingFees, $requiredPaymentFees);
        }

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
        if ($total->getMageworxFeeAmount()) {
            $taxMode = $this->helperData->getTaxInCart();

            if (in_array((int)$taxMode, [TaxConfig::DISPLAY_TYPE_BOTH, TaxConfig::DISPLAY_TYPE_INCLUDING_TAX])) {
                $applied = $total->getMageworxFeeDetails();
                if (is_string($applied)) {
                    $applied = $this->helperData->unserializeValue($applied);
                }

                if ($applied) {
                    $result = [
                        'code'      => $this->getCode(),
                        'title'     => __('Additional Fees (Incl. Tax)'),
                        'value'     => $total->getMageworxFeeAmount(),
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
