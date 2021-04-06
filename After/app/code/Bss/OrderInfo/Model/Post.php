<?php
namespace Bss\OrderInfo\Model;
class Post extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'bss_order_post';

    protected $_cacheTag = 'bss_order_post';

    protected $_eventPrefix = 'bss_order_post';

    protected function _construct()
    {
        $this->_init('Bss\OrderInfo\Model\ResourceModel\Post');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}
