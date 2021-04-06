<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Api;

interface QuoteFeeManagerInterface
{
    /**
     * Get collection of the available cart fees for the quote
     *
     * @param int $cartId
     * @return array
     */
    public function getAvailableCartFees($cartId);

    /**
     * Get only required cart fees for the quote
     *
     * @param int $cartId
     * @return array
     */
    public function getRequiredCartFees($cartId);

    /**
     * Get collection of the available shipping fees for the quote
     *
     * @param int $cartId
     * @return array
     */
    public function getAvailableShippingFees($cartId);

    /**
     * Get only required shipping fees for the quote
     *
     * @param int $cartId
     * @return array
     */
    public function getRequiredShippingFees($cartId);

    /**
     * Get collection of the available payment fees for the quote
     *
     * @param int $cartId
     * @return array
     */
    public function getAvailablePaymentFees($cartId);

    /**
     * Get only required payment fees for the quote
     *
     * @param int $cartId
     * @return array
     */
    public function getRequiredPaymentFees($cartId);

    /**
     * Get collection of the available product fees for the quote by items
     *
     * return array [
     *     [
     *        'quoteItemId'  => quoteItemId,
     *        'feesDetails'  => feesArray
     *     ]
     *]
     *
     * @param int $cartId
     * @return array
     */
    public function getAvailableProductFees($cartId);

    /**
     * Get only required products fees for the quote by items
     *
     * return array [
     *     [
     *        'quoteItemId'  => quoteItemId,
     *        'feesDetails'  => feesArray
     *     ]
     *]
     *
     * @param int $cartId
     * @return array
     */
    public function getRequiredProductFees($cartId);

    /**
     * Set cart fee
     *
     * @param int $cartId
     * @param string[] $feeData
     * @return array
     */
    public function setCartFees($cartId, $feeData);

    /**
     * Set shipping fee
     *
     * @param int $cartId
     * @param string[] $feeData
     * @return array
     */
    public function setShippingFees($cartId, $feeData);

    /**
     * Set payment fee
     *
     * @param int $cartId
     * @param string[] $feeData
     * @return array
     */
    public function setPaymentFees($cartId, $feeData);

    /**
     * Set product fee
     *
     * @param int $cartId
     * @param string[] $feeData
     * @return array
     */
    public function setProductFees($cartId, $feeData);
}
