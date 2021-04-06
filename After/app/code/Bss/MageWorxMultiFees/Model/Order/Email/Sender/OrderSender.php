<?php

namespace Bss\MageWorxMultiFees\Model\Order\Email\Sender;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;

class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender
{
    private $helperData;

    private $helperAdmin;

    protected $_productFactory;

    public function __construct(
        Template $templateContainer,
        OrderIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        OrderResource $orderResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        ManagerInterface $eventManager,
        \MageWorx\MultiFees\Helper\Data $helperData,
        \Magento\Sales\Helper\Admin $helperAdmin,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer,
            $paymentHelper,
            $orderResource,
            $globalConfig,
            $eventManager
        );
        $this->helperData = $helperData;
        $this->helperAdmin = $helperAdmin;
        $this->_productFactory = $productFactory;
    }

    /**
     * @param Order $order
     * @return boolean
     */
    protected function hasFreeShip($order){
        $total = $order->getGrandTotal();
        $valueRequire = $this->globalConfig->getValue('carriers/matrixrate/minimum_input',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($total >= $valueRequire){
            return true;
        }
        return false;
    }

    protected function prepareTemplate(Order $order)
    {
        $customerName = $order->getBillingAddress()->getFirstName().' '.$order->getBillingAddress()->getLastName();
        $statusHistorys = $order->getStatusHistoryCollection()->addFieldToFilter(
            'automatic', ['eq' => 1]
        );
        $shippingInsurance = '';
        if ($statusHistorys->getSize()) {
            foreach ($statusHistorys as $history) {
                $shippingInsurance = $history->getComment();
                break;
            }
        }

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
        foreach($order->getAllVisibleItems() as $item){
            $_product = $this->_productFactory->create()->load($item->getProductId());
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

        $a06 = ($this->hasFreeShip($order) && !$free) ? false : true;
        $purchaseInstructions = $this->globalConfig->getValue('payment/purchaseorder/instructions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $a = $order->getStatusHistoryCollection(true)->getLastItem()->getComment();
        $checkShippingMEthod_freeshipping_freeshipping = false;
        $checkShippingMEthod_flatrate_flatrate = false;
        if($order->getShippingMethod() == 'freeshipping_freeshipping'){
        $checkShippingMEthod_freeshipping_freeshipping = true;
        }
        if($order->getShippingMethod() == 'flatrate_flatrate'){
            $checkShippingMEthod_flatrate_flatrate = true;
        }
        $transport = [
            'order' => $order,
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'shipping_freeshipping_freeshipping' => $checkShippingMEthod_freeshipping_freeshipping,
            'shipping_flatrate_flatrate' => $checkShippingMEthod_flatrate_flatrate,
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'customer_name' => $customerName,
            'shipping_insurance' => $shippingInsurance,
            'order_data' =>[
                'bold_order_comment' => $order->getData('bold_order_comment'),
                'default_order_comment' => $a,
                'backorder_order_comment0' => $a00?'':('<br><b>The following items were Special order on this order</b><br>'.implode('<br>',$a00_backorder_admin).'<br>'),
                'backorder_order_comment1' => $a01?'':('<b>The following items were Back order on this order</b><br>'.implode('<br>',$a01_backorder_admin).'<br>'),
                'backorder_order_comment2' => $a02?'':('<b>The following items were Back order on this order</b><br>'.implode('<br>',$a02_backorder_admin).'<br>'),
                'backorder_order_comment3' => $a03?'':('<b>The following items were Back order on this order</b><br>'.implode('<br>',$a03_backorder_admin).'<br>'),
                'backorder_order_comment4' => $a04?'':('<b>The following items were Back order on this order</b><br>'.implode('<br>',$a04_backorder_admin).'<br>'),
                'backorder_order_comment5' => $a05?'':('<b>The following items were Back order on this order</b><br>'.implode('<br>',$a05_backorder_admin).'<br><br>'),
                'purchase_instructions' => ($order->getPayment()->getMethod() == 'purchaseorder')?$purchaseInstructions:'',
                'free_shipping' => $a06 ? '': '<br><b>Items free shipping only on this order :</b><br>'.implode("<br>",$free_shipping).'<br><br>',
                'pickup_only' => $pickup?'':('<b>The following items were Pickup Only, no delivery:</b><br>'.implode('<br>',$pickup_only).'<br>')
            ]
        ];
        $multiFees = '';
        if ($feeDetails = $order->getMageworxFeeDetails()) {
            $feeDetails = json_decode($feeDetails, true);
            foreach ($feeDetails as $feeId => $fee) {
                if (isset($fee['type']) &&
                    $fee['type'] == \MageWorx\MultiFees\Model\AbstractFee::SHIPPING_TYPE
                ) {
                    $multiFees .= '<span>';
                    $multiFees .= $fee['title'];
                    $multiFees .= '</span>';
                    $multiFees .= '<br/>';
                    foreach ($fee['options'] as $optionId => $option) {
                        $multiFees .= '<span>';
                        $multiFees .= $option['title'];
                        $multiFees .= $this->getOptionPriceHtml($option, $order);
                        $multiFees .= '</span>';
                    }
                }
            }
        }
        if ($multiFees) {
            $transport['mageworx_multifees'] = $multiFees;
        }

        $transportObject = new DataObject($transport);

        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_order_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject, 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());

        \Magento\Sales\Model\Order\Email\Sender::prepareTemplate($order);
    }

    private function getOptionPriceHtml($option, $order)
    {
        $string = ' - ';

        if ($this->getTaxInSales() == \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX) {
            $price     = $option['price'];
            $basePrice = $option['base_price'];
        } else {
            $price     = $option['price'] - $option['tax'];
            $basePrice = $option['base_price'] - $option['base_tax'];
        }

        $string .= $this->helperAdmin->displayPrices($order, $basePrice, $price);

        if ($this->getTaxInSales() == \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH) {
            $string .= __(
                '(Incl. Tax %1)',
                $this->helperAdmin->displayPrices(
                    $order,
                    $option['base_price'],
                    $option['price']
                )
            );
        };

        return $string;
    }

    private function getTaxInSales()
    {
        return $this->helperData->getTaxInSales();
    }
}
