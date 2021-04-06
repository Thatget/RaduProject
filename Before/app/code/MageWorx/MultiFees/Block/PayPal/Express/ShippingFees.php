<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\PayPal\Express;

use MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection;

/**
 * Class ShippingFees
 */
class ShippingFees extends AbstractFee
{
    /**
     * Get corresponding fee collection
     *
     * @return AbstractCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getMultifees()
    {
        return $this->feeCollectionManager->getShippingFeeCollection();
    }
}