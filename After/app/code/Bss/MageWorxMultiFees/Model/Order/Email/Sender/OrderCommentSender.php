<?php

namespace Bss\MageWorxMultiFees\Model\Order\Email\Sender;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\OrderCommentIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\NotifySender;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;
class OrderCommentSender extends \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender
{
	public function send(Order $order, $notify = true, $comment = '')
    {
    	$customerName = $order->getBillingAddress()->getFirstName().' '.$order->getBillingAddress()->getLastName();
        $transport = [
            'order' => $order,
            'comment' => $comment,
            'billing' => $order->getBillingAddress(),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'customer_name' => $customerName,
        ];
        $transportObject = new DataObject($transport);

        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_order_comment_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());

        return $this->checkAndSend($order, $notify);
    }
}