<?php
namespace Bss\OrderInfo\Model\ResourceModel\Post;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'value_id';
    protected $_eventPrefix = 'bss_order_post_collection';
    protected $_eventObject = 'post_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Bss\OrderInfo\Model\Post', 'Bss\OrderInfo\Model\ResourceModel\Post');
    }

}
