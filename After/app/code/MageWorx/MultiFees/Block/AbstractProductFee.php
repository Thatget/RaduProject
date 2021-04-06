<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block;

use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;

/**
 * Invoice view  comments form
 *
 */
abstract class AbstractProductFee extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'order/product_fee_info.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    protected $helperData;

    /**
     * @var \MageWorx\MultiFees\Helper\ProductFee
     */
    protected $helperProductFee;

    /**
     * @var \Magento\Sales\Helper\Admin
     */
    protected $helperAdmin;

    /**
     * AbstractProductFee constructor.
     *
     * @param \MageWorx\MultiFees\Helper\ProductFee $helperProductFee
     * @param TemplateContext $context
     * @param Registry $registry
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     * @param \Magento\Sales\Helper\Admin $helperAdmin
     * @param array $data
     */
    public function __construct(
        \MageWorx\MultiFees\Helper\ProductFee $helperProductFee,
        TemplateContext $context,
        Registry $registry,
        \MageWorx\MultiFees\Helper\Data $helperData,
        \Magento\Sales\Helper\Admin $helperAdmin,
        array $data = []
    ) {
        $this->helperProductFee = $helperProductFee;
        $this->helperData       = $helperData;
        $this->helperAdmin      = $helperAdmin;
        $this->coreRegistry     = $registry;
        $this->_isScopePrivate  = true;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    abstract public function getSource();

    /**
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCurrentItemId()
    {
        $itemId = null;
        if ($this->getParentBlock()) {
            $item = $this->getParentBlock()->getItem();
        } else {
            $item = $this->getItem();
        }

        if($item) {
            $itemId = $item->getQuoteItemId();
        }

        return $itemId;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order\Invoice
     */
    public function getInvoice()
    {
        return $this->coreRegistry->registry('current_invoice');
    }

    /**
     * @return \Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuote()
    {
    return $this->helperProductFee->getQuote();
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getCreditmemo()
    {
        return $this->coreRegistry->registry('current_creditmemo');
    }


    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFeeDetails()
    {
        $fees = [];

        $fullInfo = $this->getSource()->getMageworxProductFeeDetails();

        if ($fullInfo) {
            $fullInfo = $this->helperData->unserializeValue($fullInfo);
            foreach ($fullInfo as $feeId => $quoteItemData) {
                foreach ($quoteItemData as $quoteItemId => $fee) {
                    if (isset($fee['type']) && $this->getCurrentItemId() == $quoteItemId) {
                        $fees[\MageWorx\MultiFees\Model\AbstractFee::PRODUCT_TYPE][$feeId] = $fee;
                    }
                }
            }
        }

        return $fees;
    }

    /**
     * @return string
     */
    public function getTaxInSales()
    {
        return $this->helperData->getTaxInSales();
    }

    /**
     * @param array $option
     * @return string
     */
    public function getOptionPriceHtml($option)
    {
        $string = '';
        if (isset($option['percent'])) {
            $string .= (float)$option['percent'] . '%';
        }

        $string .= ' - ';

        if ($this->getTaxInSales() == \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX) {
            $price     = $option['price'];
            $basePrice = $option['base_price'];
        } else {
            $price     = $option['price'] - $option['tax'];
            $basePrice = $option['base_price'] - $option['base_tax'];
        }

        $string .= $this->helperAdmin->displayPrices($this->getSource(), $basePrice, $price);

        if ($this->getTaxInSales() == \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH) {
            $string .= __(
                '(Incl. Tax %1)',
                $this->helperAdmin->displayPrices(
                    $this->getSource(),
                    $option['base_price'],
                    $option['price']
                )
            );
        };

        if ($string) {
            $string .= '<br>';
        }

        return $string;
    }

    /**
     * @param array $fee
     * @return string
     */
    public function getFeeDateHtml($fee)
    {
        $string = '';
        if (isset($fee['date']) && $fee['date']) {
            $string .= $this->escapeHtml(trim($fee['date_title'], ':')) . ': <i>';
            $string .= $this->escapeHtml($fee['date']) . '</i><br/>';
        }

        return $string;
    }

    /**
     * @param array $fee
     * @return string
     */
    public function getFeeMessageHtml($fee)
    {
        $string = '';
        if (isset($fee['message']) && $fee['message']) {
            $string .= $this->escapeHtml(trim($fee['message_title'], ':'));
            $string .= ': <i>' . $this->escapeHtml($fee['message']) . '</i>';
        }

        return $string;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getBlockTitle()
    {
        return  __('Additional Product Fees');
    }
}
