<?php

namespace Bss\MageWorxMultiFees\Observer;

use Magento\Sales\Model\Order\Status\HistoryFactory;

class AddOrderCommentToOrder implements \Magento\Framework\Event\ObserverInterface
{
    private $historyFactory;

    public function __construct(
        HistoryFactory $historyFactory
    ) {
        $this->historyFactory = $historyFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getOrder();
        $inclInsurance = '**Transit Insurance Purchased (Loss or Damage is covered)';
        $exclInsurance = '**Transit Insurance NOT Purchased (Loss or Damage is not covered)';
        if ($orderId = $order->getId()) {
            $statusHistorys = $order->getAllStatusHistory();
            $isApply = true;
            $shippingMethod = $order->getShippingMethod();
            if (strpos($shippingMethod, 'matrixrate') !== false) {
                foreach ($statusHistorys as $history) {
                    $automatic = $history->getAutomatic();
                    if ($automatic) {
                        $isApply = false;
                        break;
                    }
                }

                if ($isApply) {
                    if ($order->getBaseMageworxFeeAmount() > 0) {
                        $comment = $inclInsurance;
                    } else {
                        $comment = $exclInsurance;
                    }
                    $status = $order->getStatus();
                    $history = $this->historyFactory->create();
                    $history->setComment($comment)
                        ->setParentId($orderId)
                        ->setIsVisibleOnFront(false)
                        ->setIsCustomerNotified(0)
                        ->setEntityName('order')
                        ->setStatus($status)
                        ->setAutomatic(true)
                        ->save();
                }
            }
        }
    }
}