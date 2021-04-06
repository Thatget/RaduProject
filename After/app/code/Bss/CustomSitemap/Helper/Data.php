<?php
namespace Bss\CustomSitemap\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
/**
 * 
 */
class Data extends AbstractHelper
{
	protected $_categoryFactory;

	public function __construct(
	    \Magento\Catalog\Model\CategoryFactory $categoryFactory
	)
	{
	    $this->_categoryFactory = $categoryFactory;
	}

	public function getCategoryCollection($level = 2){
	    return $this->_categoryFactory->create()
	    	->getCollection()->addAttributeToSelect('*')
            ->addAttributeToFilter('level', ['in' => $level])
            ->addAttributeToFilter('is_active',1)
            ->setOrder('position','ASC');
        
	}
}