<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Controller\Adminhtml\Fee\Cart;

use MageWorx\MultiFees\Model\CartFee as FeeModel;

class MassEnable extends MassDisable
{
    /**
     * @return int
     */
    protected function getActionValue()
    {
        return FeeModel::STATUS_ENABLED;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    protected function getErrorMessage()
    {
        return __('An error occurred while enabling Fees.');
    }

    /**
     * @param int $collectionSize
     * @return \Magento\Framework\Phrase
     */
    protected function getSuccessMessage($collectionSize)
    {
        return __('A total of %1 Fees have been enabled.', $collectionSize);
    }
}
