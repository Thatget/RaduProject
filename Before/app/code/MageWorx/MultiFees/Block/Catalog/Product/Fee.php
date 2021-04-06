<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Catalog\Product;

use Magento\Framework\Exception\LocalizedException;
use MageWorx\MultiFees\Helper\Data;
use Magento\Framework\View\Element\Template;
use \Magento\Framework\Registry;
use \MageWorx\MultiFees\Model\Config\Source\Position;

class Fee extends Template
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \MageWorx\MultiFees\Helper\ProductFee
     */
    protected $feeHelper;

    /**
     * @var array|\Magento\Checkout\Block\Checkout\LayoutProcessorInterface[]
     */
    protected $layoutProcessors;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Fee constructor.
     *
     * @param \MageWorx\MultiFees\Helper\ProductFee $feeHelper
     * @param Registry $registry
     * @param Data $helper
     * @param Template\Context $context
     * @param array $layoutProcessors
     * @param array $data
     */
    public function __construct(
        \MageWorx\MultiFees\Helper\ProductFee $feeHelper,
        Registry $registry,
        Data $helper,
        Template\Context $context,
        array $layoutProcessors = [],
        array $data = []
    ) {
        $this->registry         = $registry;
        $this->helper           = $helper;
        $this->feeHelper        = $feeHelper;
        $this->layoutProcessors = $layoutProcessors;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isShowProductFee()
    {
        if ($this->feeHelper->isProductPage()) {
            return $this->helper->isEnable() && $this->helper->isDisplayProductFee();
        }

        return $this->helper->isEnable();
    }

    /**
     * @return bool
     */
    public function isShowAboveButton()
    {
        return $this->helper->getProductFeePosition() == Position::POSITION_ABOVE;
    }

    /**
     * Retrieve serialized JS layout configuration ready to use in template
     *
     * @return string
     */
    public function getJsLayout()
    {
        $this->prepareCurrentItem();
        foreach ($this->layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }

        if (!$this->feeHelper->isProductPage()) {
            $this->registry->unregister('current_item');
        }

        return \Zend_Json::encode($this->jsLayout);
    }

    /**
     * @return int
     */
    public function getCurrentItemId()
    {
        $this->prepareCurrentItem();

        $id   = 0;
        $item = $this->registry->registry('current_item');
        if ($item) {
            $id = $item->getItemId();
        }

        return $id;
    }

    /**
     * Prepares current item and it's product.
     *
     * @return void
     */
    private function prepareCurrentItem()
    {
        $item = $this->registry->registry('current_item');
        $product = $this->registry->registry('current_product');

        if ($item && $product) {
            return;
        }

        try {
            $parentBlock = $this->getParentBlock();
            $layout      = $this->getLayout();
            if ($layout) {
                $additionalProductInfoBlock = $layout->getBlock('additional.product.info');
            } else {
                $additionalProductInfoBlock = null;
            }

            if ($parentBlock) {
                $item = $parentBlock->getItem();
            } elseif ($additionalProductInfoBlock) {
                /** @var \Magento\Quote\Model\Quote\Item $item */
                $item = $additionalProductInfoBlock->getItem();
            }

            if ($item) {
                $this->registry->unregister('current_item');
                $this->registry->register('current_item', $item);
                $this->registry->unregister('current_product');
                $this->registry->register('current_product', $item->getProduct());
            }
        } catch (LocalizedException $exception) {
            $this->_logger->error($exception->getLogMessage());
        }
    }
}