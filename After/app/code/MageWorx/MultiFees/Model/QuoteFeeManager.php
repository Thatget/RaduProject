<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Model\Quote;
use MageWorx\MultiFees\Helper\Data;
use MageWorx\MultiFees\Helper\Fee as HelperFee;
use MageWorx\MultiFees\Helper\ProductFee as HelperProductFee;
use MageWorx\MultiFees\Api\QuoteFeeManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use MageWorx\MultiFees\Model\ResourceModel\Fee\CartFeeCollectionFactory;
use MageWorx\MultiFees\Model\ResourceModel\Fee\PaymentFeeCollectionFactory;
use MageWorx\MultiFees\Model\ResourceModel\Fee\ProductFeeCollectionFactory;
use MageWorx\MultiFees\Model\ResourceModel\Fee\ShippingFeeCollectionFactory;

class QuoteFeeManager implements QuoteFeeManagerInterface
{
    /**
     * @var HelperFee
     */
    protected $helperFee;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var HelperProductFee
     */
    protected $helperProductFee;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var CartFeeCollectionFactory
     */
    protected $cartFeeCollectionFactory;

    /**
     * @var PaymentFeeCollectionFactory
     */
    protected $paymentFeeCollectionFactory;

    /**
     * @var ProductFeeCollectionFactory
     */
    protected $productFeeCollectionFactory;

    /**
     * @var ShippingFeeCollectionFactory
     */
    protected $shippingFeeCollectionFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * QuoteFeeManager constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param CartFeeCollectionFactory $cartFeeCollectionFactory
     * @param PaymentFeeCollectionFactory $paymentFeeCollectionFactory
     * @param ProductFeeCollectionFactory $productFeeCollectionFactory
     * @param ShippingFeeCollectionFactory $shippingFeeCollectionFactory
     * @param CartRepositoryInterface $cartRepository
     * @param HelperProductFee $helperProductFee
     * @param HelperFee $helperFee
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        CartFeeCollectionFactory $cartFeeCollectionFactory,
        PaymentFeeCollectionFactory $paymentFeeCollectionFactory,
        ProductFeeCollectionFactory $productFeeCollectionFactory,
        ShippingFeeCollectionFactory $shippingFeeCollectionFactory,
        CartRepositoryInterface $cartRepository,
        HelperProductFee $helperProductFee,
        Data $helperData,
        HelperFee $helperFee
    ) {
        $this->registry                     = $registry;
        $this->cartFeeCollectionFactory     = $cartFeeCollectionFactory;
        $this->paymentFeeCollectionFactory  = $paymentFeeCollectionFactory;
        $this->productFeeCollectionFactory  = $productFeeCollectionFactory;
        $this->shippingFeeCollectionFactory = $shippingFeeCollectionFactory;
        $this->cartRepository               = $cartRepository;
        $this->helperProductFee             = $helperProductFee;
        $this->helperData                   = $helperData;
        $this->helperFee                    = $helperFee;
    }

    /**
     * @param int $cartId
     * @param string $type
     * @param bool $required
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getFees($cartId, $type, $required = false)
    {
        /** @var Quote $quote */
        $quote         = $this->cartRepository->get($cartId);
        $feeCollection = $this->getFeeCollection($quote->getStoreId(), $type, $required);
        $feeCollection = $this->validateFeeCollectionByAddress($feeCollection, $quote);

        return $this->prepareFeesArray($feeCollection);
    }

    /**
     * Get collection of the available cart fees for the quote
     *
     * @param int $cartId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAvailableCartFees($cartId)
    {
        return $this->getFees($cartId, AbstractFee::CART_TYPE);
    }

    /**
     * Get only required cart fees for the quote
     *
     * @param int $cartId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRequiredCartFees($cartId)
    {
        return $this->getFees($cartId, AbstractFee::CART_TYPE, true);
    }

    /**
     * Get collection of the available shipping fees for the quote
     *
     * @param int $cartId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAvailableShippingFees($cartId)
    {
        return $this->getFees($cartId, AbstractFee::SHIPPING_TYPE);
    }

    /**
     * Get only required shipping fees for the quote
     *
     * @param int $cartId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRequiredShippingFees($cartId)
    {
        return $this->getFees($cartId, AbstractFee::SHIPPING_TYPE, true);
    }

    /**
     * Get collection of the available payment fees for the quote
     *
     * @param int $cartId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAvailablePaymentFees($cartId)
    {
        return $this->getFees($cartId, AbstractFee::PAYMENT_TYPE);
    }

    /**
     * Get only required payment fees for the quote
     *
     * @param int $cartId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRequiredPaymentFees($cartId)
    {
        return $this->getFees($cartId, AbstractFee::PAYMENT_TYPE, true);
    }

    /**
     * @param int $cartId
     * @param bool $required
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProductFees($cartId, $required = false)
    {
        /** @var Quote $quote */
        $quote         = $this->cartRepository->get($cartId);
        $fees          = [];
        $feeCollection = $this->getFeeCollection($quote->getStoreId(), AbstractFee::PRODUCT_TYPE, $required);
        $feeArray      = $this->prepareFeesArray($feeCollection);

        foreach ($quote->getAllItems() as $item) {
            $fees[$item->getId()]['quoteItemId'] = $item->getId();

            $itemFees = $this->helperProductFee->validateFeeCollectionByQuoteItem(
                $feeCollection,
                $item
            );

            $itemFeesArray = [];
            foreach ($itemFees->getAllIds() as $id) {
                $itemFeesArray[] = $feeArray[$id];
            }

            $fees[$item->getId()]['feesDetails'] = $itemFeesArray;
        }

        return $fees;
    }

    /**
     * Get collection of the available product fees for the quote
     *
     * @param int $cartId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAvailableProductFees($cartId)
    {
        return $this->getProductFees($cartId);
    }

    /**
     * Get only required products fees for the quote
     *
     * @param int $cartId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRequiredProductFees($cartId)
    {
        return $this->getProductFees($cartId, true);
    }

    /**
     * @param int $cartId
     * @param string[] $fees
     * @return array
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setCartFees($cartId, $fees)
    {
        return $this->setFees($cartId, $fees, AbstractFee::CART_TYPE);
    }

    /**
     * @param int $cartId
     * @param string[] $fees
     * @return array
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setShippingFees($cartId, $fees)
    {
        return $this->setFees($cartId, $fees, AbstractFee::SHIPPING_TYPE);
    }

    /**
     * @param int $cartId
     * @param string[] $fees
     * @return array
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setPaymentFees($cartId, $fees)
    {
        return $this->setFees($cartId, $fees, AbstractFee::PAYMENT_TYPE);
    }

    /**
     * @param int $cartId
     * @param string[] $fees
     * @param string $type
     * @return array
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function setFees($cartId, $fees, $type)
    {
        if (!isset($fees['id'])) {
            throw new InputException(__('A required field "id" missed.'));
        }

        if (!isset($fees['options'])) {
            throw new InputException(__('A required field "options" missed.'));
        }

        /** @var Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $this->registry->register('current_quote', $quote);
        $preparedFees = $this->prepareFeeData($fees, $type);
        $this->helperFee->addFeesToQuote(
            $preparedFees,
            $quote->getStoreId(),
            true,
            $type,
            0
        );

        $itemsCount = $quote->getItemsCount();
        if ($itemsCount) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $this->cartRepository->save($quote);
        }

        return $this->helperData->unserializeValue($quote->getData('mageworx_fee_details'));
    }

    /**
     * @param array $fees
     * @param string $type
     * @return array
     */
    protected function prepareFeeData($fees, $type)
    {
        $result = [];
        if (!isset($fees['options']) || !isset($fees['id'])) {
            return $result;
        }

        $options    = explode(',', $fees['options']);
        $newOptions = [];
        foreach ($options as $option) {
            $newOptions[$option] = [];
        }

        $result[$fees['id']]['options'] = $newOptions;
        $result[$fees['id']]['type']    = $type;

        return $result;
    }

    /**
     * @param array $fees
     * @param string $type
     * @param int $itemId
     * @return array
     */
    protected function prepareProductFeeData($fees, $type, $itemId)
    {
        $result = [];
        if (!isset($fees['options']) || !isset($fees['id'])) {
            return $result;
        }

        $options    = explode(',', $fees['options']);
        $newOptions = [];
        foreach ($options as $option) {
            $newOptions[$option] = [];
        }

        $result[$fees['id']][$itemId]['options'] = $newOptions;
        $result[$fees['id']][$itemId]['type']    = $type;

        return $result;
    }

    /**
     * @param int $cartId
     * @param string[] $fees
     * @return array|mixed
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setProductFees($cartId, $fees)
    {
        if (!isset($fees['id'])) {
            throw new InputException(__('A required field "id" missed.'));
        }

        if (!isset($fees['options'])) {
            throw new InputException(__('A required field "options" missed.'));
        }

        if (!isset($fees['itemId'])) {
            throw new InputException(__('A required field "itemId" missed.'));
        }

        /** @var Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $this->registry->register('current_quote', $quote);
        $this->helperProductFee->setCurrentQuoteItemId($fees['itemId']);
        $preparedFees = $this->prepareProductFeeData($fees, AbstractFee::PRODUCT_TYPE, $fees['itemId']);
        $this->helperProductFee->addFeesToQuote(
            $preparedFees,
            $quote->getStoreId(),
            true,
            AbstractFee::PRODUCT_TYPE,
            0
        );

        $itemsCount = $quote->getItemsCount();
        if ($itemsCount) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $this->cartRepository->save($quote);
        }

        return $this->helperData->unserializeValue($quote->getData('mageworx_product_fee_details'));
    }

    /**
     * @param int|null $storeId
     * @param string $type
     * @param bool $required
     * @return \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getFeeCollection($storeId, $type, $required = false)
    {
        switch ($type) {
            case AbstractFee::CART_TYPE:
                $feeCollectionFactory = $this->cartFeeCollectionFactory;
                break;
            case AbstractFee::PAYMENT_TYPE:
                $feeCollectionFactory = $this->paymentFeeCollectionFactory;
                break;
            case AbstractFee::SHIPPING_TYPE:
                $feeCollectionFactory = $this->shippingFeeCollectionFactory;
                break;
            case AbstractFee::PRODUCT_TYPE:
                $feeCollectionFactory = $this->productFeeCollectionFactory;
                break;
            default:
                throw new Exception(__('Unknown fee type %1', $type));
        }

        /** @var \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection */
        $feeCollection = $feeCollectionFactory->create();

        $feeCollection
            ->setValidationFilter(
                $storeId,
                $this->helperFee->getCustomerGroupId()
            )
            ->addRequiredFilter($required)
            ->addIsDefaultFilter(false)
            ->addIsActiveFilter()
            ->addSortOrder()
            ->addLabels();

        foreach ($feeCollection as $key => $fee) {
            $fee->setStoreId($storeId);
        }

        return $feeCollection;
    }

    /**
     * @param \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection
     * @param Quote $quote
     * @return \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection
     * @return \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateFeeCollectionByAddress($feeCollection, $quote)
    {
        $address = $this->helperFee->getSalesAddress($quote);
        /**
         * @var \MageWorx\MultiFees\Model\AbstractFee $fee
         */
        foreach ($feeCollection as $key => $fee) {
            if (!$fee->canProcessFee($address, $quote)) {
                $feeCollection->removeItemByKey($key);
                continue;
            }
        }

        return $feeCollection;
    }

    /**
     * @param \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection
     * @return array
     */
    protected function prepareFeesArray($feeCollection)
    {
        $feeArray = [];
        foreach ($feeCollection as $fee) {
            $feeArray[$fee->getId()] = $fee->getData();
            $options                 = [];
            foreach ($fee->getOptions() as $option) {
                $options[$option->getId()] = $option->getData();
            }
            $feeArray[$fee->getId()]['options'] = $options;
        }

        return $feeArray;
    }
}