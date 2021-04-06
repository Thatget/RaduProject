<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use MageWorx\MultiFees\Helper\Data as Helper;
use MageWorx\MultiFees\Helper\Fee as HelperFee;
use MageWorx\MultiFees\Helper\Price as HelperPrice;
use MageWorx\MultiFees\Model\AbstractFee;
use MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollectionFactory;
use MageWorx\MultiFees\Api\Data\FeeInterface;

/**
 * Class LayoutProcessor
 */
abstract class LayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @var \Magento\Checkout\Block\Checkout\AttributeMerger
     */
    protected $merger;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    protected $countryCollection;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\Collection
     */
    protected $regionCollection;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterface
     */
    protected $defaultShippingAddress;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var HelperFee
     */
    protected $helperFee;

    /**
     * @var HelperPrice
     */
    protected $helperPrice;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var FeeFormInputPlant
     */
    protected $feeFormInputRendererFactory;

    /**
     * @var AbstractCollectionFactory
     */
    protected $feeCollectionFactory;

    /**
     * @var AbstractCollection
     */
    protected $feeCollection;

    /**
     * LayoutProcessor constructor.
     *
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $merger
     * @param Helper $helper
     * @param HelperFee $helperFee
     * @param HelperPrice $helperPrice
     * @param FeeFormInputPlant $feeFormInputRendererFactory
     * @param \Magento\Framework\Escaper $escaper
     * @param AbstractCollectionFactory $feeCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Checkout\Block\Checkout\AttributeMerger $merger,
        Helper $helper,
        HelperFee $helperFee,
        HelperPrice $helperPrice,
        AbstractCollectionFactory $feeCollectionFactory,
        \MageWorx\MultiFees\Block\FeeFormInputPlant $feeFormInputRendererFactory,
        \Magento\Framework\Escaper $escaper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->merger                      = $merger;
        $this->helper                      = $helper;
        $this->helperFee                   = $helperFee;
        $this->helperPrice                 = $helperPrice;
        $this->feeCollectionFactory        = $feeCollectionFactory;
        $this->feeFormInputRendererFactory = $feeFormInputRendererFactory;
        $this->escaper                     = $escaper;
        $this->logger                      = $logger;
    }

    /**
     * Add our multifees components to the layout if the specific container exists
     *
     * @param array $jsLayout
     * @return array
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     */
    abstract public function process($jsLayout);

    /**
     * @param array $jsLayout
     * @return mixed
     */
    protected function addApplyOnClickData($jsLayout)
    {
        $isApplyOnClick = $this->helper->isApplyOnClick();
        if (isset($jsLayout['components']['mageworx-fee-form-container'])) {
            $jsLayout['components']['mageworx-fee-form-container']['applyOnClick'] = $isApplyOnClick;
        }

        if (isset(
            $jsLayout['components']['checkout']['children']['sidebar']['children']
            ['summary']['children']['itemsBefore']['children']['mageworx-fee-form-container']
        )) {
            $jsLayout['components']['checkout']['children']['sidebar']['children']
            ['summary']['children']['itemsBefore']['children']['mageworx-fee-form-container']
            ['applyOnClick'] = $isApplyOnClick;
        }

        return $jsLayout;
    }

    /**
     * Get components for the available cart fees
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getFeeComponents()
    {
        $quote               = $this->helperFee->getQuote();
        $address             = $this->helperFee->getSalesAddress($quote);
        $feeCollection       = $this->getFeeCollection($quote->getStoreId());
        $feeCollection       = $this->validateFeeCollectionByAddress($feeCollection, $address);
        $this->feeCollection = $feeCollection;

        $components = $this->convertFeeCollectionToComponentsArray($feeCollection);

        return $components;
    }


    /**
     * @param array $feeCollection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getExtraComponents($feeCollection)
    {
        $components = [];
        /**
         * @var \MageWorx\MultiFees\Model\AbstractFee $fee
         */
        foreach ($feeCollection as $key => $fee) {
            if ($fee->getEnableDateField() || $fee->getEnableCustomerMessage()) {
                $details = $this->helperFee->getQuoteDetailsMultifees();
                if ($fee->getEnableDateField()) {
                    $components[] = $this->getFeeDateComponent($fee, $details);
                }
                if ($fee->getEnableCustomerMessage()) {
                    $components[] = $this->getFeeCustomerMessageComponent($fee, $details);
                }
            }
        }

        return $components;
    }

    /**
     * @param int|null $storeId
     * @return \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getFeeCollection($storeId)
    {

        /** @var \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection */
        $feeCollection = $this->feeCollectionFactory->create();

        $feeCollection
            ->setValidationFilter(
                $storeId,
                $this->helperFee->getCustomerGroupId()
            )
            ->addRequiredFilter(false)
            ->addIsDefaultFilter(false)
            ->addIsActiveFilter()
            ->addSortOrder()
            ->addLabels();

        if ($this->helperFee->isProductPage()) {
            $feeCollection->addFieldToFilter('input_type', FeeInterface::FEE_INPUT_TYPE_HIDDEN);
        } else {
            $feeCollection->addFieldToFilter('input_type', ['nin' => FeeInterface::FEE_INPUT_TYPE_HIDDEN]);
        }

        foreach ($feeCollection as $key => $fee) {
            $fee->setStoreId($storeId);
        }

        return $feeCollection;
    }

    /**
     * @param \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection
     * @param int $address
     * @return \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection
     */
    public function validateFeeCollectionByAddress($feeCollection, $address)
    {
        /**
         * @var \MageWorx\MultiFees\Model\AbstractFee $fee
         */
        foreach ($feeCollection as $key => $fee) {
            if (!$fee->canProcessFee($address, $this->helperFee->getQuote())) {
                $feeCollection->removeItemByKey($key);
                continue;
            }
        }

        return $feeCollection;
    }

    /**
     * Create js-layout components for each fee in the collection
     * Not a fee specific method.
     *
     * @param \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function convertFeeCollectionToComponentsArray(
        \MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection $feeCollection
    ) {
        $details    = $this->helperFee->getQuoteDetailsMultifees();
        $components = [];
        /** @var \MageWorx\MultiFees\Model\AbstractFee $fee */
        foreach ($feeCollection as $fee) {
            /** @var \MageWorx\MultiFees\Block\FeeFormInput\FeeFormInputRenderInterface $renderer */
            $renderer     = $this->feeFormInputRendererFactory->create($fee, ['details' => $details]);
            $components[] = $renderer->render();
        }

        return $components;
    }

    /**
     * @param \MageWorx\MultiFees\Api\Data\FeeInterface $fee
     * @param array $details
     * @return array
     */
    protected function getFeeDateComponent($fee, $details)
    {
        $isApplyOnClick = $this->helper->isApplyOnClick();
        if ($fee->getDateFieldTitle()) {
            $label = $fee->getDateFieldTitle();
        } else {
            $label = __('Date for') . ' "' . $fee->getTitle() . '"';
        }

        $scope                     = $this->getScope($fee->getType());
        $component                 = [];
        $component['component']    = 'MageWorx_MultiFees/js/form/element/date';
        $component['config']       = [
            'customScope' => $scope,
            'template'    => 'MageWorx_MultiFees/form/field',
            'elementTmpl' => 'ui/form/element/date'
        ];
        $component['dataScope']    = $scope . '.fee[' . $fee->getId() . '][date]';
        $component['label']        = $label;
        $component['provider']     = 'checkoutProvider';
        $component['visible']      = true;
        $component['validation']   = [];
        $component['applyOnClick'] = $isApplyOnClick;
        $component['sortOrder']    = $this->getSortOrder($fee->getSortOrder(), 2);

        if (!empty($details[$fee->getId()]['date'])) {
            $component['value'] = $this->escaper->escapeHtml($details[$fee->getId()]['date']);
        }

        return $component;
    }

    /**
     * @param \MageWorx\MultiFees\Model\AbstractFee $fee
     * @param array $details
     * @return array
     */
    protected function getFeeCustomerMessageComponent($fee, $details)
    {
        $isApplyOnClick = $this->helper->isApplyOnClick();
        if ($fee->getCustomerMessageTitle()) {
            $label = $fee->getCustomerMessageTitle();
        } else {
            $label = __('Message for') . ' "' . $fee->getTitle() . '"';
        }

        $scope                  = $this->getScope($fee->getType());
        $component              = [];
        $component['component'] = 'MageWorx_MultiFees/js/form/element/textarea';
        $component['config']    = [
            'customScope' => $scope,
            'template'    => 'MageWorx_MultiFees/form/field',
        ];

        $component['dataScope']    = $scope . '.fee[' . $fee->getId() . '][message]';
        $component['label']        = $label;
        $component['provider']     = 'checkoutProvider';
        $component['visible']      = true;
        $component['validation']   = [];
        $component['applyOnClick'] = $isApplyOnClick;
        $component['sortOrder']    = $this->getSortOrder($fee->getSortOrder(), 1);

        if (!empty($details[$fee->getId()]['message'])) {
            $component['value'] = $this->escaper->escapeHtml($details[$fee->getId()]['message']);
        }

        return $component;
    }

    /**
     * @param int $feeSortOrder
     * @param int $i
     * @return mixed
     */
    protected function getSortOrder($feeSortOrder, $i = 0)
    {
        return $feeSortOrder * 5 + $i;
    }

    /**
     * Return scope for fee type
     *
     * @param int type
     * @return string
     */
    protected function getScope($type)
    {
        switch ($type) {
            case AbstractFee::CART_TYPE:
                return 'mageworxFeeForm';
            case AbstractFee::SHIPPING_TYPE:
                return 'mageworxShippingFeeForm';
            case AbstractFee::PAYMENT_TYPE:
                return 'mageworxPaymentFeeForm';
            case AbstractFee::PRODUCT_TYPE:
                return 'mageworxProductFeeForm';
            default:
                return 'mageworxFeeForm';
        }
    }
}
