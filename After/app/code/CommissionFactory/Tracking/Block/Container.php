<?php

namespace CommissionFactory\Tracking\Block;

class Container extends \Magento\Framework\View\Element\Template
{

    protected $_orderRepository;

    protected $_registry;

    /**
     * @param \Magento\Framework\View\Element\Template $context
     */
    public function __construct(
    	\Magento\Backend\Block\Template\Context $context,
    	\Magento\Sales\Model\OrderRepository $_orderRepository ,
    	\Magento\Framework\Registry $_registry,
    	array $data = []
    ) {
    	$this->_orderRepository = $_orderRepository;
    	$this->_registry = $_registry;
        parent::__construct($context, $data);
    }

    protected function _toHtml()
	{
		if (empty($this->getAdvertiserId()))
		{
			return "";
		}

		return "<script async src=\"https://t.cfjump.com/tag/" . htmlspecialchars($this->getAdvertiserId()) . "\"></script>\n";
	}

	public function getAdvertiserId()
	{
		return $this->_registry->registry('advertiserId');
	}

	public function setAdvertiserId($advertiserId)
	{
		$this->_registry->register('advertiserId', $advertiserId);
	}
}
