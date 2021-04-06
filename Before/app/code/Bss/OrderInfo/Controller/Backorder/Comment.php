<?php


namespace Bss\OrderInfo\Controller\Backorder;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;

class Comment extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CheckoutSession
    */
    protected $checkoutSession;

    /**
     * @var ProductFactory
     */
    protected $_productFacrory;

    /**
     * constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param JsonFactory $resultJsonFactory
     */


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_productFacrory = $productFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

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
        $pickup_only = array();
        $pickup = true;

        $quote = $this->checkoutSession->getQuote()->getAllVisibleItems();
        foreach ($quote as $item){
            $_product = $this->_productFacrory->create()->load($item->getProduct()->getId());
            $attribute0 = $_product->getResource()->getAttribute('a01_specialorder_admin');
            $attribute1 = $_product->getResource()->getAttribute('a01_backorder_admin');
            $attribute2 = $_product->getResource()->getAttribute('a02_backorder_admin');
            $attribute3 = $_product->getResource()->getAttribute('a03_backorder_admin');
            $attribute4 = $_product->getResource()->getAttribute('a04_backorder_admin');
            $attribute5 = $_product->getResource()->getAttribute('a05_backorder_admin');
            $attribute6 = $_product->getResource()->getAttribute('pickup_only');
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
            if ($attribute6 &&  $attribute6->getFrontend()->getValue($_product) != 'No Pickup'
                && $attribute6->getFrontend()->getValue($_product) != ''){
                $pickup_only[] = '- '.$_product->getName().' ('. $attribute6->getStoreLabel().')';
                $pickup = false;
            }
        }
        $backordercomment['backorder_order_comment0'] = $a00?'':('<b>The following items were Special order on this order</b><br>' .implode('<br>',$a00_backorder_admin).'<br>');
        $backordercomment['backorder_order_comment1'] = $a01?'':('<b>The following items were Back order on this order</b><br>' .implode('<br>',$a01_backorder_admin).'<br>');
        $backordercomment['backorder_order_comment2'] = $a02?'':('<b>The following items were Back order on this order</b><br>' .implode('<br>',$a02_backorder_admin).'<br>');
        $backordercomment['backorder_order_comment3'] = $a03?'':('<b>The following items were Back order on this order</b><br>' .implode('<br>',$a03_backorder_admin).'<br>');
        $backordercomment['backorder_order_comment4'] = $a04?'':('<b>The following items were Back order on this order</b><br>' .implode('<br>',$a04_backorder_admin).'<br>');
        $backordercomment['backorder_order_comment5'] = $a05?'':('<b>The following items were Back order on this order</b><br>' .implode('<br>',$a05_backorder_admin).'<br>');
        $backordercomment['pickup_only'] = $pickup?'':('<b>The following items were Pickup only, no delivery</b><br>' .implode('<br>',$pickup_only).'<br>');

        $resultJson = $this->resultJsonFactory->create();
        $html = implode('<br>',$backordercomment);
        $response = [
            'message' => $html
        ];
        return $resultJson->setData($response);
    }
}