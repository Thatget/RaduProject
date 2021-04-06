<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Total\Quote;

use MageWorx\MultiFees\Api\FeeCollectionManagerInterface;

abstract class AbstractFee extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * @var int
     */
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
     * @var FeeCollectionManagerInterface
     */
    protected $feeCollectionManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \MageWorx\MultiFees\Api\Data\FeeInterface[]
     */
    protected $possibleFeesItems;

    /**
     * Fee constructor
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     * @param \MageWorx\MultiFees\Helper\Fee $helperFee
     * @param \MageWorx\MultiFees\Helper\Price $helperPrice
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
        \Magento\Tax\Helper\Data $taxHelperData,
        \MageWorx\MultiFees\Api\FeeCollectionManagerInterface $feeCollectionManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->eventManager         = $eventManager;
        $this->storeManager         = $storeManager;
        $this->priceCurrency        = $priceCurrency;
        $this->helperData           = $helperData;
        $this->helperFee            = $helperFee;
        $this->helperPrice          = $helperPrice;
        $this->taxHelperData        = $taxHelperData;
        $this->feeCollectionManager = $feeCollectionManager;
        $this->_logger              = $logger;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param bool $required
     * @param bool $isDefault
     * @return array|\MageWorx\MultiFees\Api\Data\FeeInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    abstract protected function getPossibleFeesItems($quote, $required = false, $isDefault = false);

    /**
     * Check is required fees are missed in the current quote
     *
     * @param array $multiFeesInQuote
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    abstract protected function checkIsRequiredFeesMissed(array $multiFeesInQuote = [], \Magento\Quote\Model\Quote $quote);

    /**
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param double $mageworxFeeAmount
     * @param double $mageworxFeeTaxAmount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    abstract protected function addPricesToAddress($total, $address, $mageworxFeeAmount, $mageworxFeeTaxAmount);

    /**
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param double $baseMageworxFeeAmount
     * @param double $baseMageworxFeeTaxAmount
     * @return $this
     */
    abstract protected function addBasePricesToAddress(
        $total,
        $address,
        $baseMageworxFeeAmount,
        $baseMageworxFeeTaxAmount
    );

    /**
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param array $feesData
     * @return $this
     */
    abstract protected function addFeesDetailsToAddress($total, $address, $feesData);

    /**
     * @param null $quote
     * @param null $address
     * @param array $feesPost
     * @return mixed
     */
    abstract protected function autoAddFeesByParams($quote = null, $address = null, $feesPost = []);

    /**
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return $this
     */
    abstract protected function resetAddress($address);

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
     * @param array $applied
     * @return mixed
     */
    protected function convertFeeDetailsForTax($applied)
    {
        foreach ($applied as $feeId => $feeData) {
            if (empty($feeData['options'])) {
                continue;
            }

            foreach ($feeData['options'] as $optionId => $optionData) {
                $price     = &$applied[$feeId]['options'][$optionId]['price'];
                $basePrice = &$applied[$feeId]['options'][$optionId]['base_price'];
                $price     = $this->priceCurrency->round($optionData['price'] - $optionData['tax']);
                $basePrice = $this->priceCurrency->round($optionData['base_price'] - $optionData['base_tax']);
            }
        }

        return $applied;
    }


    /**
     * @param int $addressId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getFeesData($addressId = 0)
    {
        return $this->helperFee->getQuoteDetailsMultifees($addressId);
    }

    /**
     * Get all available required fees from the corresponding collections
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \MageWorx\MultiFees\Api\Data\FeeInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function collectAllRequiredFeesItems(\Magento\Quote\Model\Quote $quote)
    {
        if ($this->possibleFeesItems !== null) {
            return $this->possibleFeesItems;
        }

        /** @var \MageWorx\MultiFees\Api\Data\FeeInterface[] $possibleFeesItems */
        $possibleFeesItems = $this->getPossibleFeesItems($quote, true);

        $possibleFees = [];
        foreach ($possibleFeesItems as $fee) {
            $possibleFees[$fee->getId()] = $fee;
        }

        $this->possibleFeesItems = $possibleFeesItems;

        return $this->possibleFeesItems;
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
        $possibleFeesCollection = $this->getPossibleFeesItems($quote);

        $possibleFees = [];
        foreach ($possibleFeesCollection as $fee) {
            $possibleFees[$fee->getId()] = $fee;
        }

        return $possibleFees;
    }
}
