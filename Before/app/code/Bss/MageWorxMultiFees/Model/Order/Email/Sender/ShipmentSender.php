<?php

namespace Bss\MageWorxMultiFees\Model\Order\Email\Sender;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Email\Container\ShipmentIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment as ShipmentResource;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;

class ShipmentSender extends \Magento\Sales\Model\Order\Email\Sender\ShipmentSender
{
    private $helperData;

    private $helperAdmin;

    public function __construct(
        Template $templateContainer,
        ShipmentIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        ShipmentResource $shipmentResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        ManagerInterface $eventManager,
        \MageWorx\MultiFees\Helper\Data $helperData,
        \Magento\Sales\Helper\Admin $helperAdmin
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer,
            $paymentHelper,
            $shipmentResource,
            $globalConfig,
            $eventManager
        );
        $this->helperData = $helperData;
        $this->helperAdmin = $helperAdmin;
    }

    public function send(Shipment $shipment, $forceSyncMode = false)
    {
        $customerName = $shipment->getBillingAddress()->getFirstName().' '.$shipment->getOrder()->getBillingAddress()->getLastName();
        $shipment->setSendEmail(true);

        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            $order = $shipment->getOrder();
            $statusHistorys = $order->getStatusHistoryCollection()->addFieldToFilter(
                'automatic', ['eq' => 1]
            );

            $shippingInsurance = '';
            if ($statusHistorys->getSize()) {
                foreach ($statusHistorys as $history) {
                    if ($history->getAutomatic()) {
                        $shippingInsurance = $history->getComment();
                        break;
                    }
                }
            }

            $transport = [
                'order' => $order,
                'shipment' => $shipment,
                'comment' => $shipment->getCustomerNoteNotify() ? $shipment->getCustomerNote() : '',
                'billing' => $order->getBillingAddress(),
                'payment_html' => $this->getPaymentHtml($order),
                'store' => $order->getStore(),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                'customer_name' => $customerName,
                'order_comment' => $shipment->getOrder()->getBoldOrderComment(),
                'shipping_insurance' => $shippingInsurance
            ];
            $multiFees = '';
            if ($feeDetails = $order->getMageworxFeeDetails()) {
                $feeDetails = json_decode($feeDetails, true);
                foreach ($feeDetails as $feeId => $fee) {
                    if (isset($fee['type']) &&
                        $fee['type'] == \MageWorx\MultiFees\Model\AbstractFee::SHIPPING_TYPE
                    ) {
                        $multiFees .= '<span>';
                        $multiFees .= $fee['title'];
                        $multiFees .= '</span>';
                        $multiFees .= '<br/>';
                        foreach ($fee['options'] as $optionId => $option) {
                            $multiFees .= '<span>';
                            $multiFees .= $option['title'];
                            $multiFees .= $this->getOptionPriceHtml($option, $order);
                            $multiFees .= '</span>';
                        }
                    }
                }
            }
            if ($multiFees) {
                $transport['mageworx_multifees'] = $multiFees;
            }

            $transportObject = new DataObject($transport);

            /**
             * Event argument `transport` is @deprecated. Use `transportObject` instead.
             */
            $this->eventManager->dispatch(
                'email_shipment_set_template_vars_before',
                ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
            );

            $this->templateContainer->setTemplateVars($transportObject->getData());

            if ($this->checkAndSend($order)) {
                $shipment->setEmailSent(true);
                $this->shipmentResource->saveAttribute($shipment, ['send_email', 'email_sent']);
                return true;
            }
        } else {
            $shipment->setEmailSent(null);
            $this->shipmentResource->saveAttribute($shipment, 'email_sent');
        }

        $this->shipmentResource->saveAttribute($shipment, 'send_email');

        return false;
    }

    private function getOptionPriceHtml($option, $order)
    {
        $string = ' - ';

        if ($this->getTaxInSales() == \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX) {
            $price     = $option['price'];
            $basePrice = $option['base_price'];
        } else {
            $price     = $option['price'] - $option['tax'];
            $basePrice = $option['base_price'] - $option['base_tax'];
        }

        $string .= $this->helperAdmin->displayPrices($order, $basePrice, $price);

        if ($this->getTaxInSales() == \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH) {
            $string .= __(
                '(Incl. Tax %1)',
                $this->helperAdmin->displayPrices(
                    $order,
                    $option['base_price'],
                    $option['price']
                )
            );
        };

        return $string;
    }

    private function getTaxInSales()
    {
        return $this->helperData->getTaxInSales();
    }
}
