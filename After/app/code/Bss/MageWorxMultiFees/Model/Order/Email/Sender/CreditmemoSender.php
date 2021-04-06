<?php

namespace Bss\MageWorxMultiFees\Model\Order\Email\Sender;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\Container\CreditmemoIdentity;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as CreditmemoResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;

class CreditmemoSender extends \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender
{
	private $helperData;

    private $helperAdmin;

	public function __construct(
        Template $templateContainer,
        CreditmemoIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        CreditmemoResource $creditmemoResource,
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
        	$creditmemoResource,
        	$globalConfig,
        	$eventManager
        );
        $this->helperData = $helperData;
        $this->helperAdmin = $helperAdmin;
    }

	public function send(Creditmemo $creditmemo, $forceSyncMode = false)
    {
        $creditmemo->setSendEmail(true);

        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            $order = $creditmemo->getOrder();

            $transport = [
                'order' => $order,
                'creditmemo' => $creditmemo,
                'comment' => $creditmemo->getCustomerNoteNotify() ? $creditmemo->getCustomerNote() : '',
                'billing' => $order->getBillingAddress(),
                'payment_html' => $this->getPaymentHtml($order),
                'store' => $order->getStore(),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
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
                'email_creditmemo_set_template_vars_before',
                ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
            );

            $this->templateContainer->setTemplateVars($transportObject->getData());

            if ($this->checkAndSend($order)) {
                $creditmemo->setEmailSent(true);
                $this->creditmemoResource->saveAttribute($creditmemo, ['send_email', 'email_sent']);
                return true;
            }
        } else {
            $creditmemo->setEmailSent(null);
            $this->creditmemoResource->saveAttribute($creditmemo, 'email_sent');
        }

        $this->creditmemoResource->saveAttribute($creditmemo, 'send_email');

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
