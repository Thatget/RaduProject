<?php
/**
 * Created by PhpStorm.
 * User: milan
 * Date: 26-11-15
 * Time: 13:52
 */
namespace PluginCompany\ProductPdf\Block;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PluginCompany\ProductPdf\Model\Product\PdfUrl;

class Link extends Template {

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var UrlInterface
     */
    protected $frameworkUrl;
    /**
     * @var PdfUrl
     */
    private $pdfUrlBuilder;

    public function __construct(
        Context $context,
        Registry $registry,
        PdfUrl $pdfUrlBuilder,
        array $data = []
    ){
        $this->storeManager = $context->getStoreManager();
        $this->registry = $registry;
        $this->scopeConfig = $context->getScopeConfig();
        $this->frameworkUrl = $context->getUrlBuilder();
        $this->pdfUrlBuilder = $pdfUrlBuilder;
        parent::__construct(
            $context,
            $data
        );
        $this->setTemplate('PluginCompany_ProductPdf::link.phtml');
    }

    public function getPdfUrl()
    {
        return $this->getPdfUrlForProduct($this->getProduct());
    }

    private function getPdfUrlForProduct($product)
    {
        return $this->pdfUrlBuilder
            ->setProduct($product)
            ->setStoreId($this->getCurrentStoreId())
            ->getPdfUrl()
            ;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    private function getProduct()
    {
        return $this->registry->registry('product');
    }

    private function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    public function isEnabled(){
        return $this->scopeConfig
            ->isSetFlag(
                "plugincompany_productpdf/frontend/enable_extension",
                ScopeInterface::SCOPE_STORE
            );
    }

    public function getLinkCss(){
        $css = $this->scopeConfig
            ->getValue(
                'plugincompany_productpdf/frontend/link_css',
                ScopeInterface::SCOPE_STORE
            );
        if($this->displayIcon()){
            $css .= ';text-decoration:none!important;';
        }
        return $css;
    }

    public function getIconCss(){
        return $this->scopeConfig
            ->getValue(
                'plugincompany_productpdf/frontend/icon_css',
                ScopeInterface::SCOPE_STORE
            );
    }

    public function displayType(){
        return $this->scopeConfig
            ->getValue(
                'plugincompany_productpdf/frontend/show_as',
                ScopeInterface::SCOPE_STORE
            );
    }

    public function getLinkText(){
        $text = $this->scopeConfig
            ->getValue(
                'plugincompany_productpdf/frontend/link_text',
                ScopeInterface::SCOPE_STORE
            );
        if(!$text){
            $text = __("Download PDF");
        }
        return $text;
    }

    public function displayLink(){
        return in_array($this->displayType(), ['link', 'icon_and_link']);
    }

    public function displayIcon(){
        return in_array($this->displayType(), ['icon', 'icon_and_link']);
    }

    public function getIconUrl(){
        if($this->getCustomIconPath()){
            return $this->getCustomIconUrl();
        }
        return $this->getViewFileUrl('PluginCompany_ProductPdf::img/pdficon.png');
    }

    private function getCustomIconPath()
    {
        return $this->scopeConfig->getValue(
            'plugincompany_productpdf/frontend/icon_img',
            ScopeInterface::SCOPE_STORE
        );
    }

    private function getCustomIconUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl('media')
            . 'plugincompany/productpdf/'
            . $this->getCustomIconPath()
        ;
    }

    public function getLinkClasses(){
        return $this->scopeConfig
            ->getValue(
                'plugincompany_productpdf/frontend/link_classes',
                ScopeInterface::SCOPE_STORE
            );
    }

    public function getJsOptionsJSON()
    {
        return json_encode($this->getJsOptions());
    }

    public function getJsOptions()
    {
        return [
            'linkSelector' => $this->getLinkPlacementClass(),
            'insertMethod' => $this->getInsertMethod(),
            'childProductUrls' => $this->getConfigurableChildUrls(),
            'mainProductUrl' => $this->getPdfUrl()
        ];
    }

    public function getLinkPlacementClass()
    {
        $selector = $this->getLinkPlacementSelectorValue();
        if($selector == 'custom_selector'){
            return $this->getCustomSelectorClass();
        }
        return $selector;
    }

    private function getLinkPlacementSelectorValue()
    {
        return $this->scopeConfig
            ->getValue(
                'plugincompany_productpdf/frontend/link_placement_selector',
                ScopeInterface::SCOPE_STORE
            );
    }

    private function getCustomSelectorClass()
    {
        return $this->scopeConfig
            ->getValue(
                'plugincompany_productpdf/frontend/link_custom_css_selector',
                ScopeInterface::SCOPE_STORE
            );
    }

    public function getInsertMethod()
    {
        return $this->scopeConfig
            ->getValue(
                'plugincompany_productpdf/frontend/link_placement_method',
                ScopeInterface::SCOPE_STORE
            );
    }

    private function getConfigurableChildUrls()
    {
        $product = $this->getProduct();
        if($product->getTypeId() != 'configurable') {
            return [];
        }
        if(!$this->useConfigurableChild()) {
            return [];
        }
        $urls = [];
        foreach($this->getChildProductCollection($product) as $child) {
            /** @var Product $child */
            $urls[$child->getId()] = $this->getPdfUrlForProduct($child);
        }
        return $urls;
    }

    private function useConfigurableChild()
    {
        return $this->scopeConfig
            ->isSetFlag(
                'plugincompany_productpdf/configurable_product/use_child_product',
                ScopeInterface::SCOPE_STORE
            );
    }

    /**
     * @param $product
     * @return Collection
     */
    private function getChildProductCollection($product)
    {
        return $product
            ->getTypeInstance()
            ->getUsedProductCollection($product)
            ->addAttributeToSelect('name')
            ;
    }

}
