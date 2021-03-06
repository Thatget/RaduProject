<?php
namespace Bss\OrderInfo\Block\Config;

class Identifier  extends \Magento\Checkout\Block\Onepage\Success implements \Magento\Framework\DataObject\IdentityInterface
{

    protected $_countryFactory;
    protected $_filterProvider;
    protected $_blockFactory;
    protected $_orderFactory;

    public function __construct(
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    )
    {
        $this->_countryFactory = $countryFactory;
        $this->_filterProvider = $filterProvider;
        $this->_blockFactory = $blockFactory;
        $this->_orderFactory = $orderFactory;
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
    }

    public function getOrder()
    {
        return $this->_checkoutSession->getLastRealOrder();
    }

    protected function _toHtml()
    {
        $blockId = $this->getBlockId();
        $html = '';
        if ($blockId) {
            $storeId = $this->_storeManager->getStore()->getId();
            /** @var \Magento\Cms\Model\Block $block */
            $block = $this->_blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            if ($block->isActive()) {
                $orderId = $this->_checkoutSession->getLastRealOrder()->getId();
                if ($orderId) {
                    $country = $this->_countryFactory->create();
                    $order = $this->_orderFactory->create()->load($orderId);
                    $array['order_info']['grandtotal'] = $order->getGrandTotal()?:0;
                    $array['order_info']['payment_name'] = $order->getPayment()->getMethodInstance()->getTitle()?:'';
                    $array['order_info']['payment_code'] = $order->getPayment()->getMethod();
                    $array['order_info']['shipping_method_title'] = $order->getShippingDescription()?:'';
                    $array['order_info']['shipping_method_code'] = $order->getShippingMethod();
                    $array['order_info']['shipping_address_city'] = $order->getShippingAddress()->getCity()?:'';
                    $array['order_info']['shipping_address_street'] = $order->getShippingAddress()->getData('street')?:'';
                    $array['order_info']['shipping_address_region'] = $order->getShippingAddress()->getRegion()?:'';
                    $array['order_info']['shipping_address_country'] = $country->load($order->getShippingAddress()->getCountryId())->getName()?:'';
                    $array['order_info']['billing_address_city'] = $order->getBillingAddress()->getCity()?:'';
                    $array['order_info']['billing_address_street'] = $order->getBillingAddress()->getData('street')?:'';
                    $array['order_info']['billing_address_region'] = $order->getBillingAddress()->getRegion()?:'';
                    $array['order_info']['billing_address_country'] = $country->load($order->getBillingAddress()->getCountryId())->getName()?:'';
                    $array['order_info']['shipping_address'] = (($array['order_info']['shipping_address_street']=='')?'*':$array['order_info']['shipping_address_street'])
                        .' , '.(($array['order_info']['shipping_address_city']=='')?'*':$array['order_info']['shipping_address_city'])
                        .' , '.(($array['order_info']['shipping_address_region']=='')?'*':$array['order_info']['shipping_address_region'])
                        .' , '.(($array['order_info']['shipping_address_country']=='')?'*':$array['order_info']['shipping_address_country']);
                    $array['order_info']['billing_address'] = (($array['order_info']['billing_address_street']=='')?'*':$array['order_info']['billing_address_street'])
                        .' , '.(($array['order_info']['billing_address_city']=='')?'*':$array['order_info']['billing_address_city'])
                        .' , '.(($array['order_info']['billing_address_region']=='')?'*':$array['order_info']['billing_address_region'])
                        .' , '.(($array['order_info']['billing_address_country']=='')?'*':$array['order_info']['billing_address_country']);
                    $array['check_if']['purchaseorder']=($array['order_info']['payment_code']=="purchaseorder")?true:false;
                    $array['check_if']['checkmo']=($array['order_info']['payment_code']=="checkmo")?true:false;
                    $array['check_if']['banktransfer']=($array['order_info']['payment_code']=="banktransfer")?true:false;
                    $array['check_if']['stripe_payments']=($array['order_info']['payment_code']=="stripe_payments")?true:false;
                    $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->setVariables($array);
                    $html = $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
                }
            }
        }
        return $html;
    }

    /**
     * @inheritDoc
     */
    public function getIdentities()
    {
        // TODO: Implement getIdentities() method.
    }
}