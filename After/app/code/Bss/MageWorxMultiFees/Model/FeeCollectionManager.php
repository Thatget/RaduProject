<?php

namespace Bss\MageWorxMultiFees\Model;

class FeeCollectionManager extends \MageWorx\MultiFees\Model\FeeCollectionManager
{
	public function getShippingFeeCollection($required = false, $isDefault = false)
    {
        $key = 'required' . (int)$required . 'default' . (int)$isDefault;
        if (!empty($this->loadedShippingFeeCollection[$key])) {
            return $this->loadedShippingFeeCollection[$key];
        }

        // Get multifees
        $quote   = $this->getQuote();
        $address = $this->getAddress(\Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_SHIPPING);
        /** @var \MageWorx\MultiFees\Model\ResourceModel\Fee\ShippingFeeCollection $feeCollection */
        $feeCollection = $this->shippingFeeCollectionFactory->create();

        $feeCollection
            ->setValidationFilter(
                $quote->getStoreId(),
                $this->helperFee->getCustomerGroupId()
            )
            ->addRequiredFilter($required)
            ->addIsDefaultFilter($isDefault)
            ->addIsActiveFilter()
            ->addSortOrder()
            ->addLabels();

        $shippingMethod = $address->getShippingMethod();
        if (strpos($shippingMethod, 'matrixrate_matrixrate') !== false) {
        	$shippingMethod = 'matrixrate_matrixrate';
        }

        /**
         * @var \MageWorx\MultiFees\Model\ShippingFee $fee
         */
        foreach ($feeCollection as $key => $fee) {
            $shippingMethods = $fee->getShippingMethods();
            if (!empty($shippingMethods) && !in_array($shippingMethod, $shippingMethods)) {
                $feeCollection->removeItemByKey($key);
                continue;
            }

            if (!$fee->canProcessFee($address, $quote)) {
                $feeCollection->removeItemByKey($key);
                continue;
            }

            $fee->setStoreId($quote->getStoreId());
        }
        // Get multifees end

        $this->loadedShippingFeeCollection[$key] = $feeCollection;

        return $this->loadedShippingFeeCollection[$key];
    }
}
