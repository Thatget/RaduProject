<?php

namespace Bss\RwMagentoCapcha\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomObserverMethod implements ObserverInterface
{
    /**
     * custom event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
      //logic herer
    }
}