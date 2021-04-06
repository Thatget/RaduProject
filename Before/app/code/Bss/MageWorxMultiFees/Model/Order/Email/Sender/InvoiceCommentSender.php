<?php

namespace Bss\MageWorxMultiFees\Model\Order\Email\Sender;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\InvoiceCommentIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\NotifySender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;

class InvoiceCommentSender extends \Magento\Sales\Model\Order\Email\Sender\InvoiceCommentSender
{

	public function send(Invoice $invoice, $notify = true, $comment = '')
    {
    	$customerName = $invoice->getBillingAddress()->getFirstName().' '.$invoice->getOrder()->getBillingAddress()->getLastName();
        $order = $invoice->getOrder();
        $transport = [
            'order' => $order,
            'invoice' => $invoice,
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
            'email_invoice_comment_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());

        return $this->checkAndSend($order, $notify);
    }
}