<?php

namespace Bss\MageWorxMultiFees\Model\Order\Email\Sender;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\ShipmentCommentIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\NotifySender;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;

class ShipmentCommentSender extends \Magento\Sales\Model\Order\Email\Sender\ShipmentCommentSender
{
	public function send(Shipment $shipment, $notify = true, $comment = '')
    {
    	$customerName = $shipment->getBillingAddress()->getFirstName().' '.$shipment->getOrder()->getBillingAddress()->getLastName();
        $order = $shipment->getOrder();
        $transport = [
            'order' => $order,
            'shipment' => $shipment,
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
            'email_shipment_comment_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());

        return $this->checkAndSend($order, $notify);
    }
}