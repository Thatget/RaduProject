<?php
namespace Bss\OrderInfo\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper{


    const PATH_LIST = 'block_identifer/general/listid';
    protected $_scopeConfig;

    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function getBlockId(){
        return $this->_scopeConfig->getValue(self::PATH_LIST,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}