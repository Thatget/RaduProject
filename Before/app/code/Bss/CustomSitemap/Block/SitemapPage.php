<?php


namespace Bss\CustomSitemap\Block;
use Mageplaza\Sitemap\Block\Sitemap;
use Magento\Catalog\Helper\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\Sitemap\Helper\Data as HelperConfig;
/**
 * Class Sitemap
 * @package Mageplaza\Sitemap\Block
 */
class SitemapPage extends \Mageplaza\Sitemap\Block\Sitemap
{
    public function renderHtmlSitemapPage()
    {
        $htmlSitemap = '';
        $htmlSitemap .= $this->renderSection('page', $this->_helper->iisEnablePageSitemap(), 'Pages', $this->getPageCollection());
        $htmlSitemap .= $this->renderSection('link', $this->_helper->isEnableAddLinksSitemap(), 'Additional links', $this->getAdditionLinksCollection());

        return $htmlSitemap;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if($collection = $this->getCollection())
        {
            $limit = [];
            $limit[] = $this->_helper->getProductLimit() ? $this->_helper->getProductLimit() : self::DEFAULT_PRODUCT_LIMIT;
            $pager = $this->getLayout()->createBlock('Magento\Theme\Block\Html\Pager','product.sitemap.pager')
            ->setAvailableLimit($limit)
            ->setShowPerPage(false)
            ->setCollection($collection);
            $this->setChild('pager', $pager);
            $collection->load();
        }
        return $this;
    }
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
    public function getCollection()
    {
        $pageSize = $this->_helper->getProductLimit() ? $this->_helper->getProductLimit() : self::DEFAULT_PRODUCT_LIMIT;
        $page = ($this->getRequest()->getParam('p'))? $this->getRequest()->getParam('p') : 1;
        $collection = $this->productCollection->addAttributeToSelect('*')
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->setPageSize($pageSize)
            ->setCurPage($page)
            ->load();
            return $collection;
    }

    public function renderHtmlSitemapProduct()
    {
        $collection = $this->getCollection();
        $htmlSitemap = '';
        $htmlSitemap .= $this->renderSection('product', $this->_helper->isEnableProductSitemap(), 'Products', $collection);
        $htmlSitemap .= $this->renderSection('link', $this->_helper->isEnableAddLinksSitemap(), 'Additional links', $this->getAdditionLinksCollection());

        return $htmlSitemap;
    }
    public function checkEnableCategorySitemap(){
        return $this->_helper->isEnableCategorySitemap();
    }
    public function showCategory()
    {
        $categories = $this->_categoryHelper->getStoreCategories();
        $htmlSitemap = '<ul>';
        foreach($categories as $category) {
            $htmlSitemap .= '<li class="'.$category->getLevel().'">'.'<a href="'.$this->_categoryHelper->getCategoryUrl($this->categoryRepository->get($category->getId())).'">'.$category->getName().'</a>';
            $categoryObj = $this->categoryRepository->get($category->getId());
            $subcategories = $categoryObj->getChildrenCategories();
            if (!empty($subcategories)) {
                $htmlSitemap .= '<ul>';
                foreach($subcategories as $subcategorie) {
                    $htmlSitemap .= '<li class="'.$subcategorie->getLevel().'">'.'<a href="'.$this->_categoryHelper->getCategoryUrl($this->categoryRepository->get($subcategorie->getId())).'">'.$subcategorie->getName().'</a>';
                    $subcategories2 = $this->categoryRepository->get($subcategorie->getId())->getChildrenCategories();
                    if (!empty($subcategories2)) {
                        $htmlSitemap .= '<ul>';
                        foreach ($subcategories2 as $subcategories3) {
                            $htmlSitemap .= '<li class="'.$subcategories3->getLevel().'">'.'<a href="'.$this->_categoryHelper->getCategoryUrl($this->categoryRepository->get($subcategories3->getId())).'">'.$subcategories3->getName().'</a></li>';
                        }
                        $htmlSitemap .= '</ul>';
                    }
                    $htmlSitemap .= '</li>';
                }
                $htmlSitemap .= '</ul>';
            }
            $htmlSitemap .= '</li>';
        }
        $htmlSitemap .= '</ul>';
        return $htmlSitemap;
    }

    public function renderHtmlSitemapCategory()
    {
        $htmlSitemap = '';
        $htmlSitemap .= $this->renderSection('category', $this->_helper->isEnableCategorySitemap(), 'Categories', $this->getCategoryCollection());
        $htmlSitemap .= $this->renderSection('link', $this->_helper->isEnableAddLinksSitemap(), 'Additional links', $this->getAdditionLinksCollection());

        return $htmlSitemap;
    }


}
