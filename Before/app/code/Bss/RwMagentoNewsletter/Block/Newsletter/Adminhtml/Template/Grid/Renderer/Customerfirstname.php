<?php
namespace Bss\RwMagentoNewsletter\Block\Newsletter\Adminhtml\Template\Grid\Renderer;
use Magento\Framework\DataObject;
class Customerfirstname extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public function render(\Magento\Framework\DataObject $row)
    {
        if($row->getData('type')==1) {
            return ($row->getData('customer_firstname')!=''?$row->getData('customer_firstname'):'----');
        }else{
            return ($row->getData('customer_firstname')!=''?$row->getData('customer_firstname'):'----');
        }
    }
}
