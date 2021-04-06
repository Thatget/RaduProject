<?php 

namespace Bss\MageWorxMultiFees\Rewrite\Magento\Customer\Controller\Account;

class CreatePost extends \Magento\Customer\Controller\Account\CreatePost
{

	protected $_registry;
	public function __construct
	(
		\Magento\Framework\Registry $registry
	)
	{
		$this->_registry = $registry;
	}

	public function beforeExecute(\Magento\Customer\Controller\Account\CreatePost $CreatePost)
	{
		$this->_registry->register('customer_password', $CreatePost->getRequest()->getParam('password'));
	}
}