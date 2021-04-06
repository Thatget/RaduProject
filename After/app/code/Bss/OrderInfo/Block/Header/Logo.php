<?php
namespace Bss\OrderInfo\Block\Header;

class Logo extends \Magento\Theme\Block\Html\Header\Logo{

    /**
     * Current template name
     *
     * @var string
     */
    protected $_template = 'Bss_OrderInfo::html/header/logo.phtml';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    )
    {
        parent::__construct($context, $fileStorageHelper, $data);
    }

    public function ActionName(){
        return $this->_request->getFullActionName();
    }
}