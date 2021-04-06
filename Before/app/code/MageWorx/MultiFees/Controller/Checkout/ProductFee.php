<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Controller\Checkout;

class ProductFee extends AbstractFee
{
    /**
     * Fee constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \MageWorx\MultiFees\Helper\ProductFee $helperProductFee
     * @param \MageWorx\MultiFees\Helper\BillingAddressManager $billingAddressManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \MageWorx\MultiFees\Helper\ProductFee $helperProductFee,
        \MageWorx\MultiFees\Helper\BillingAddressManager $billingAddressManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Cart $cart,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $context,
            $quoteRepository,
            $billingAddressManager,
            $storeManager,
            $cart,
            $logger
        );
        $this->helperFee = $helperProductFee;
    }

    /**
     * @param array $feesPost
     * @return mixed
     */
    protected function prepareFeeData($feesPost)
    {
        foreach ($feesPost as $feeId => $itemData) {
            foreach ($itemData as $itemId => $feeData) {
                $requestedItemId = $this->getRequest()->getPost('quote_item_id');
                if ($requestedItemId && $itemId != $requestedItemId) {
                    unset($feesPost[$feeId][$itemId]);
                    continue;
                }

                if (empty($feeData['options'])) {
                    continue;
                }

                $options    = is_array($feeData['options']) ? $feeData['options'] : explode(',', $feeData['options']);
                $newOptions = [];
                foreach ($options as $option) {
                    $newOptions[$option] = [];
                }

                $feesPost[$feeId][$itemId]['options'] = $newOptions;
                $feesPost[$feeId][$itemId]['type']    = $this->getRequest()->getPost('type');
            }
        }

        return $feesPost;
    }
}
