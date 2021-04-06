<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Api;

/**
 * Fee CRUD interface.
 *
 * @api
 */
interface ProductFeeRepositoryInterface
{
    /**
     * Save fee
     *
     * @param \MageWorx\MultiFees\Api\Data\ProductFeeInterface $fee
     * @return \MageWorx\MultiFees\Model\ProductFee|\MageWorx\MultiFees\Api\Data\ProductFeeInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\MageWorx\MultiFees\Api\Data\ProductFeeInterface $fee);

    /**
     * Retrieve fee
     *
     * @param int $feeId
     * @return \MageWorx\MultiFees\Model\ProductFee|\MageWorx\MultiFees\Api\Data\ProductFeeInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($feeId);

    /**
     * Delete fee
     *
     * @param \MageWorx\MultiFees\Api\Data\ProductFeeInterface $fee
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\MageWorx\MultiFees\Api\Data\ProductFeeInterface $fee);

    /**
     * Delete fee by ID.
     *
     * @param int $feeId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($feeId);
}
