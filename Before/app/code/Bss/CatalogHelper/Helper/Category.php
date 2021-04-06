<?php


namespace Bss\CatalogHelper\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Category extends AbstractHelper
{

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */

    protected $registry;
	protected $category;
	protected $categoryRepository;
    protected $view;
    protected $productRepository;
    protected $filterProvider;
    public function __construct(
    	\Magento\Catalog\Model\CategoryRepository $categoryRepository,
    	\Magento\Framework\Registry $registry,
    	\Magento\Catalog\Model\Category $category,
        \Magento\Catalog\Block\Product\View $view,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Cms\Model\Template\FilterProvider  $filterProvider,
        \Magento\Framework\App\Helper\Context $context
    ) {
    	$this->categoryRepository = $categoryRepository;
    	$this->registry = $registry;
    	$this->category = $category;
        $this->view = $view;
        $this->productRepository = $productRepository;
        $this->filterProvider = $filterProvider;
        parent::__construct($context);
    }
    public function getCurentProduct() {
        return $this->view->getProduct();
    }

    public function getCurentCategory()
    {
        try {
           return $this->registry->registry('current_category');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getChildCategories()
    {
        if($this->getCurentCategory())
        {
            $id = $this->getCurentCategory()->getId();
            $childCategories = $this->category->getCategories($id);
            if(!empty($childCategories)) return $childCategories;
            else return false;
        }
        else return false;
    	
    	
    }

    public function getCategory($id, $storeId=null)
	{
		return $this->categoryRepository->get($id, $storeId);
	}

    public function truncateWords($input, $numwords, $padding="")
    {
        $output = strtok($input, " \n");
        while(--$numwords > 0) $output .= " " . strtok(" \n");
        if($output != $input) $output .= $padding;
        return $output;
    }

    function hasSpecialPrice($productSku)
    {
        $product = $this->productRepository->get($productSku);
        $regularPrice = $product->getPrice();
        $finalPrice = $product->getFinalPrice();
        return $finalPrice < $regularPrice;
    }

    function getSpecialPrice($productSku)
    {
        $product = $this->productRepository->get($productSku);
        return $product->getFinalPrice();
    }

    public function renderElement($cmsContent = '')
    {
        return $this->filterProvider->getPageFilter()->filter($cmsContent);
    }

}
