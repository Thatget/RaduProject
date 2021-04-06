<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Helper;

use MageWorx\MultiFees\Api\CartFeeRepositoryInterface;
use MageWorx\MultiFees\Api\PaymentFeeRepositoryInterface;
use MageWorx\MultiFees\Api\ProductFeeRepositoryInterface;
use MageWorx\MultiFees\Api\ShippingFeeRepositoryInterface;
use Magento\Framework\Registry;

/**
 * Class ProductFee
 */
class ProductFee extends Fee
{
    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * @var int
     */
    protected $currentQuoteItemId;

    /**
     * ProductFee constructor.
     *
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Backend\Model\Session\Quote $adminQuoteSession
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param CartFeeRepositoryInterface $cartFeeRepository
     * @param ShippingFeeRepositoryInterface $shippingFeeRepository
     * @param PaymentFeeRepositoryInterface $paymentFeeRepository
     * @param ProductFeeRepositoryInterface $productFeeRepository
     * @param \MageWorx\MultiFees\Model\ResourceModel\Option\CollectionFactory $feeOptionCollectionFactory
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param Registry $coreRegistry
     */
    public function __construct(
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\State $appState,
        \Magento\Backend\Model\Session\Quote $adminQuoteSession,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        CartFeeRepositoryInterface $cartFeeRepository,
        ShippingFeeRepositoryInterface $shippingFeeRepository,
        PaymentFeeRepositoryInterface $paymentFeeRepository,
        ProductFeeRepositoryInterface $productFeeRepository,
        \MageWorx\MultiFees\Model\ResourceModel\Option\CollectionFactory $feeOptionCollectionFactory,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        Data $helperData,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        Registry $coreRegistry
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $appState,
            $adminQuoteSession,
            $dateFilter,
            $objectManager,
            $cartFeeRepository,
            $shippingFeeRepository,
            $paymentFeeRepository,
            $productFeeRepository,
            $feeOptionCollectionFactory,
            $filterManager,
            $dataObjectFactory,
            $coreRegistry,
            $helperData,
            $resourceConnection
        );
        $this->quoteItemFactory = $quoteItemFactory;
    }

    /**
     * Return current product from registry on product page
     * OR return product from quote item on cart page
     *
     * @return \Magento\Catalog\Model\Product|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentProduct()
    {
        $product = $this->coreRegistry->registry('current_product');
        if ($product) {
            return $product;
        }

        $item = $this->getCurrentItem();
        if ($item) {
            $product = $item->getProduct();
            $this->coreRegistry->register('current_product', $product);

            return $product;
        }

        return null;
    }

    /**
     * @return \Magento\Quote\Model\Quote\Item|null
     */
    public function getCurrentItem()
    {
        return $this->coreRegistry->registry('current_item');
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return $this
     */
    public function setCurrentItem(\Magento\Quote\Model\Quote\Item $item)
    {
        if ($this->coreRegistry->registry('current_item')) {
            $this->coreRegistry->unregister('current_item');
        }
        $this->coreRegistry->register('current_item', $item);

        return $this;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setCurrentQuoteItemId($id)
    {
        $this->currentQuoteItemId = $id;

        return $this;
    }

    /**
     * @return \Magento\Quote\Model\Quote\Item
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuoteItemFromCurrentProduct()
    {
        $product = $this->getCurrentProduct();
        // create quote item from current product on product page to calculate hidden fee
        $quoteItem = $this->quoteItemFactory->create();
        $quoteItem->setProduct($product);
        $quoteItem->setQty(1);

        return $quoteItem;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSessionFeeDetail()
    {
        $source  = $this->getSourceForFeeDetail();
        $details = $source->getMageworxProductFeeDetails();
        if (is_string($details)) {
            $details = $this->helperData->unserializeValue($details);
        }

        return $details ? $details : [];
    }

    /**
     * @param array $feeDetails
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setSessionFeeDetail($feeDetails)
    {
        $source = $this->getSourceForFeeDetail();
        $source->setMageworxProductFeeDetails($feeDetails);

        return $this;
    }

    /**
     * @param array $fees
     * @param int $type
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function removeCurrentFee(array $fees, $type)
    {
        foreach ($fees as $feeId => $data) {
            if (isset($fees[$feeId][$this->getCurrentQuoteItemId()])) {
                if ($data[$this->getCurrentQuoteItemId()]['type'] == $type) {
                    // We remove fee with the current type, because they will be replaced with data from $feesPost
                    unset($fees[$feeId][$this->getCurrentQuoteItemId()]);
                }
            }
        }

        return $fees;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentQuoteItemId()
    {
        if ($this->currentQuoteItemId) {
            return $this->currentQuoteItemId;
        }
        $quoteItemId = $this->_getRequest()->getPost('quote_item_id');

        if (!$quoteItemId && $this->getCurrentItem()) {
            $quoteItemId = $this->getCurrentItem()->getItemId();
        }

        return $quoteItemId;
    }

    /**
     * @param array $feesPost
     * @param int|null $storeId
     * @param bool $collect
     * @param int $type
     * @param int $addressId
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addFeesToQuote(
        $feesPost,
        $storeId,
        $collect = true,
        $type = \MageWorx\MultiFees\Model\AbstractFee::PRODUCT_TYPE,
        $addressId = 0
    ) {
        $preparedFeePost = [];

        if ($this->getCurrentQuoteItemId()) {
            foreach ($feesPost as $feeId => $data) {
                if (isset($data[$this->getCurrentQuoteItemId()])) {
                    $preparedFeePost[$feeId] = [
                        $this->getCurrentQuoteItemId() => $data[$this->getCurrentQuoteItemId()]
                    ];
                }
            }
        } else {
            $preparedFeePost = $feesPost;
        }

        parent::addFeesToQuote($preparedFeePost, $storeId, $collect, $type, $addressId);
    }

    /**
     *
     * Return all fees from the form with the current specified type, and other types that were already in the quote
     *
     * @param array $feesQuoteData
     * @param array $feesPost
     * @param int|null $storeId
     * @return array
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function modifyFeesDetailsByPostData(array $feesQuoteData, array $feesPost, $storeId)
    {
        $filter = new \Zend_Filter();
        $filter->addFilter(new \Zend_Filter_StringTrim());
        $filter->addFilter(new \Zend_Filter_StripTags());

        $optionIds = [];
        $feeIds    = array_keys($feesPost);
        foreach ($feesPost as $feeId => $data) {
            foreach ($data as $quoteItemId => $feePostData) {
                if (empty($feePostData['options'])) {
                    unset($feesPost[$feeId][$quoteItemId]);
                    continue; // @protection: fee from the form has no options
                }

                foreach ($feePostData['options'] as $optionId => $optionData) {
                    if (!$optionId) {
                        unset($feesPost[$feeId][$quoteItemId]['options'][$optionId]);
                        continue; // @protection: empty option
                    }

                    $optionIds[] = $optionId;
                }
            }
        }

        /** @var \MageWorx\MultiFees\Model\ResourceModel\Option\Collection $optionCollection */
        $optionCollection = $this->feeOptionCollectionFactory->create();
        $optionCollection->addFeeOptionFilter(
            $optionIds
        )->addFeeFilter(
            $feeIds
        )->addStoreLanguage(
            $storeId,
            true
        )->load();


        foreach ($feesPost as $feeId => $data) {
            foreach ($data as $quoteItemId => $feePostData) {
                $data = $this->modifyFeeDetails($feeId, $feePostData, $filter, $optionCollection);
                if (!$data) {
                    unset($feesQuoteData[$feeId][$quoteItemId]);
                } else {
                    $feesQuoteData[$feeId][$quoteItemId] = $data;
                }
            }
        }

        $feesQuoteData = $this->filterMultiFeesInQuote($feesQuoteData, $storeId);

        return $feesQuoteData;
    }

    /**
     * Helper method which filter the multi-fees in the quote and removes unacceptable values like:
     * 1) multi-fees having no options
     * 2) multi-fees with not valid options
     * 3) unavailable multi-fees model
     *
     * @param array $feesQuoteData
     * @param int $storeId
     * @return array
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     */
    protected function filterMultiFeesInQuote(array $feesQuoteData = [], $storeId = 0)
    {
        $itemIds = $this->getValidQuoteItemIds();
        foreach ($feesQuoteData as $feeId => $feesData) {
            foreach ($feesData as $itemId => $data) {
                if (array_search($itemId, $itemIds) === false) {
                    unset($feesQuoteData[$feeId][$itemId]);
                    continue;
                }

                $feesQuoteData[$feeId][$itemId] = $this->filterMultiFee($feeId, $data);
                if (!$feesQuoteData[$feeId][$itemId]) {
                    unset($feesQuoteData[$feeId][$itemId]);
                }
            }
        }

        return $feesQuoteData;
    }

    /**
     * @param null $quote
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getValidQuoteItemIds($quote = null)
    {
        $quote = $quote ? $quote : $this->getQuote();

        $validItems = [];
        foreach ($quote->getAllItems() as $item) {
            if (!$item->getParentItemId()) {
                $validItems[] = $item->getItemId();
            }
        }

        return $validItems;
    }

    /**
     * @param array|\MageWorx\MultiFees\Model\ResourceModel\Fee\ProductFeeCollection $feeCollection
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array|\MageWorx\MultiFees\Model\ResourceModel\Fee\ProductFeeCollection
     */
    public function validateFeeCollectionByQuoteItem($feeCollection, $quoteItem)
    {
        /**
         * @var \MageWorx\MultiFees\Model\ProductFee $fee
         */
        foreach ($feeCollection as $key => $fee) {
            if (!$fee->getActions()->validate($quoteItem) || $quoteItem->getParentItemId()) {
                if (is_array($feeCollection)) {
                    unset($feeCollection[$key]);
                } else {
                    $feeCollection->removeItemByKey($key);
                }
            }
        }

        return $feeCollection;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \MageWorx\MultiFees\Model\AbstractFee $fee
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateItems($quote, $fee)
    {
        $validItems = [];

        if ($this->isProductPage()) {
            $this->setCurrentItem($this->getQuoteItemFromCurrentProduct());
        }

        if ($this->getCurrentItem()) {
            $product = $this->getCurrentItem();

            if ($fee->getActions()->validate($product)) {
                $validItems[] = $product;
            }
        } else {
            $validItems = parent::validateItems($quote, $fee);
        }

        return $validItems;
    }
}
