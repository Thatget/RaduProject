<?php

namespace Bss\MageWorxMultiFees\Model;

use MageWorx\MultiFees\Model\AbstractFee;

class ShippingFee extends \MageWorx\MultiFees\Model\ShippingFee
{
	public function isValidForTheAddress($address)
    {
    	$shippingMethod = $address->getShippingMethod();
        if ($shippingMethod && strpos($shippingMethod, 'matrixrate_matrixrate') !== false) {
        	$shippingMethod = 'matrixrate_matrixrate';
        }
        if (!empty($this->getShippingMethods())) {
            if (!$shippingMethod) {
                return false;
            }

            if (!in_array($shippingMethod, $this->getShippingMethods())) {
                return false;
            }
        }

        return AbstractFee::isValidForTheAddress($address);
    }
}
