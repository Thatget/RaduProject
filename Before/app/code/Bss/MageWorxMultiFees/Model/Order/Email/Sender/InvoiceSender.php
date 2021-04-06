<?php

namespace Bss\MageWorxMultiFees\Model\Order\Email\Sender;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Email\Container\InvoiceIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice as InvoiceResource;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;

class InvoiceSender extends \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
{
    private $helperData;

    private $helperAdmin;

    public function __construct(
        Template $templateContainer,
        InvoiceIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        InvoiceResource $invoiceResource,
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
            $invoiceResource,
            $globalConfig,
            $eventManager
        );
        $this->helperData = $helperData;
        $this->helperAdmin = $helperAdmin;
    }

    public function send(Invoice $invoice, $forceSyncMode = false)
    {
        $customerName = $invoice->getBillingAddress()->getFirstName().' '.$invoice->getOrder()->getBillingAddress()->getLastName();
        $invoice->setSendEmail(true);

        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            $order = $invoice->getOrder();
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
                'invoice' => $invoice,
                'comment' => $invoice->getCustomerNoteNotify() ? $invoice->getCustomerNote() : '',
                'billing' => $order->getBillingAddress(),
                'payment_html' => $this->getPaymentHtml($order),
                'store' => $order->getStore(),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                'customer_name' => $customerName,
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
                'email_invoice_set_template_vars_before',
                ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
            );

            $this->templateContainer->setTemplateVars($transportObject->getData());

            if ($this->checkAndSend($order)) {
                $invoice->setEmailSent(true);
                $this->invoiceResource->saveAttribute($invoice, ['send_email', 'email_sent']);
                return true;
            }
        } else {
            $invoice->setEmailSent(null);
            $this->invoiceResource->saveAttribute($invoice, 'email_sent');
        }

        $this->invoiceResource->saveAttribute($invoice, 'send_email');

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
