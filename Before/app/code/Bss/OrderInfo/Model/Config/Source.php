<?php

namespace Bss\OrderInfo\Model\Config;

class Source implements \Magento\Framework\Option\ArrayInterface
{

    protected $_blockFactory;
    public function __construct(
        \Magento\Cms\Model\BlockFactory $blockFactory
    ) {
        $this->_blockFactory = $blockFactory;
    }

    public function toOptionArray()
    {
        $block = $this->_blockFactory->create()->getCollection();
        $items = $block->getData();
        $arrayx = array();
        $i = 0;
        foreach ($items as $item){
            $arrayx[$i]['value'] = $item[ \Magento\Cms\Model\Block::IDENTIFIER];
            $arrayx[$i]['label'] = __($item[ \Magento\Cms\Model\Block::TITLE].'('.$item[ \Magento\Cms\Model\Block::IDENTIFIER].')');
            $i++;
        }
        return $arrayx;
    }
}