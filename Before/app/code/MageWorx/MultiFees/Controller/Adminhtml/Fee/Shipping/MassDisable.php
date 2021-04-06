<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Controller\Adminhtml\Fee\Shipping;

use MageWorx\MultiFees\Model\ShippingFee as FeeModel;

class MassDisable extends MassAction
{
    /**
     * @param FeeModel $fee
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function executeAction(FeeModel $fee)
    {
        $fee->setStatus($this->getActionValue());
        $this->feeRepository->save($fee);

        return $this;
    }

    /**
     * @return int
     */
    protected function getActionValue()
    {
        return FeeModel::STATUS_DISABLED;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    protected function getErrorMessage()
    {
        return __('An error occurred while disabling Fees.');
    }

    /**
     * @param int $collectionSize
     * @return \Magento\Framework\Phrase
     */
    protected function getSuccessMessage($collectionSize)
    {
        return __('A total of %1 Fees have been disabled.', $collectionSize);
    }
}
