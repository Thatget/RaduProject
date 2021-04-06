<?php

namespace Bold\OrderComment\Block\Order;

use Bold\OrderComment\Model\Data\OrderComment;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Sales\Model\Order;

class Comment extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_productFactory;
    protected $coreRegistry = null;
    protected $_scopeConfig;

    public function __construct(
        TemplateContext $context,
        Registry $registry,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->coreRegistry = $registry;
        $this->_isScopePrivate = true;
        $this->_template = 'order/view/comment.phtml';
        parent::__construct($context, $data);
    }

    /**
     * @param Order $order
     * @return boolean
     */
    protected function hasFreeShip($order){
        $total = $order->getGrandTotal();
        $valueRequire = $this->_scopeConfig->getValue('carriers/matrixrate/minimum_input',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($total >= $valueRequire){
            return true;
        }
        return false;
    }


    public function getBackOrderComment(){
        $backordercomment = array();
        $a00_backorder_admin = array();
        $a00 = true;
        $a01_backorder_admin = array();
        $a01 = true;
        $a02_backorder_admin = array();
        $a02 = true;
        $a03_backorder_admin = array();
        $a03 = true;
        $a04_backorder_admin = array();
        $a04 = true;
        $a05_backorder_admin = array();
        $a05 = true;
        $free_shipping = array();
        $free = true;
        $pickup_only = array();
        $pickup = true;


        $products= $this->getOrder()->getAllVisibleItems();
        foreach ($products as $item){
            $_product = $this->_productFactory->create()->load($item->getProduct()->getId());
            $attribute0 = $_product->getResource()->getAttribute('a01_specialorder_admin');
            $attribute1 = $_product->getResource()->getAttribute('a01_backorder_admin');
            $attribute2 = $_product->getResource()->getAttribute('a02_backorder_admin');
            $attribute3 = $_product->getResource()->getAttribute('a03_backorder_admin');
            $attribute4 = $_product->getResource()->getAttribute('a04_backorder_admin');
            $attribute5 = $_product->getResource()->getAttribute('a05_backorder_admin');
            $attribute6 = $_product->getResource()->getAttribute('free_shipping');
            $attribute7 = $_product->getResource()->getAttribute('pickup_only');

            if ($attribute0 &&  $attribute0->getFrontend()->getValue($_product) != 'No'
                && $attribute0->getFrontend()->getValue($_product) != ''){
                $a00_backorder_admin[] = '- '.$_product->getName().' ('. $attribute0->getStoreLabel().')';
                $a00 = false;
            }
            if ($attribute1 &&  $attribute1->getFrontend()->getValue($_product) != 'No'
                && $attribute1->getFrontend()->getValue($_product) != ''){
                $a01_backorder_admin[] = '- '.$_product->getName().' ('. $attribute1->getStoreLabel().')';
                $a01 = false;
            }
            if ($attribute2 &&  $attribute2->getFrontend()->getValue($_product) != 'No'
                && $attribute2->getFrontend()->getValue($_product) != ''){
                $a02_backorder_admin[] = '- '.$_product->getName().' ('. $attribute2->getStoreLabel().')';
                $a02 = false;
            }
            if ($attribute3 &&  $attribute3->getFrontend()->getValue($_product) != 'No'
                && $attribute3->getFrontend()->getValue($_product) != ''){
                $a03_backorder_admin[] = '- '.$_product->getName().' ('. $attribute3->getStoreLabel().')';
                $a03 = false;
            }
            if ($attribute4 &&  $attribute4->getFrontend()->getValue($_product) != 'No'
                && $attribute4->getFrontend()->getValue($_product) != ''){
                $a04_backorder_admin[] = '- '.$_product->getName().' ('. $attribute4->getStoreLabel().')';
                $a04 = false;
            }
            if ($attribute5 &&  $attribute5->getFrontend()->getValue($_product) != 'No'
                && $attribute5->getFrontend()->getValue($_product) != ''){
                $a05_backorder_admin[] = '- '.$_product->getName().' ('. $attribute5->getStoreLabel().')';
                $a05 = false;
            }
            if ($attribute6 &&  $attribute6->getFrontend()->getValue($_product) != 'No Free Shipping'
                && $attribute6->getFrontend()->getValue($_product) != ''){
                $free_shipping[] = '- '.$_product->getName().' ('. $attribute6->getStoreLabel().')';
                $free = false;
            }
            if ($attribute7 &&  $attribute7->getFrontend()->getValue($_product) != 'No Pickup'
                && $attribute7->getFrontend()->getValue($_product) != ''){
                $pickup_only[] = '- '.$_product->getName().' ('. $attribute7->getStoreLabel().')';
                $pickup = false;
            }
        }
        $a06 = ($this->hasFreeShip($this->getOrder()) && !$free) ? false : true;

        $backordercomment['free_shipping'] = $a06?'':('<b>Items free shipping only on this order :</b><br>' .implode('<br>',$free_shipping).'<br><br>');
        $backordercomment['pickup_only'] = $pickup?'':('<b>The following items were  Pickup Only, no delivery.</b><br>' .implode('<br>',$pickup_only).'<br><br>');
        $backordercomment['backorder_order_comment0'] = $a00?'':('<b>The following items were Special order on this order</b><br>' .implode('<br>',$a00_backorder_admin).'<br><br>');
        $backordercomment['backorder_order_comment1'] = $a01?'':('<b>The following items were Back order on this order</b><br>' .implode('<br>',$a01_backorder_admin).'<br><br>');
        $backordercomment['backorder_order_comment2'] = $a02?'':('<b>The following items were Back order on this order</b><br>' .implode('<br>',$a02_backorder_admin).'<br><br>');
        $backordercomment['backorder_order_comment3'] = $a03?'':('<b>The following items were Back order on this order</b><br>' .implode('<br>',$a03_backorder_admin).'<br><br>');
        $backordercomment['backorder_order_comment4'] = $a04?'':('<b>The following items were Back order on this order</b><br>' .implode('<br>',$a04_backorder_admin).'<br><br>');
        $backordercomment['backorder_order_comment5'] = $a05?'':('<b>The following items were Back order on this order</b><br>' .implode('<br>',$a05_backorder_admin).'<br><br>');
        return $backordercomment;
    }
    public function getOrder() : Order
    {
        return $this->coreRegistry->registry('current_order');
    }

    public function getOrderComment(): string
    {
        return trim($this->getOrder()->getData(OrderComment::COMMENT_FIELD_NAME));
    }

    public function hasOrderComment() : bool
    {
        return strlen($this->getOrderComment()) > 0;
    }

    public function getOrderCommentHtml() : string
    {
        return nl2br($this->escapeHtml($this->getOrderComment()));
    }
}