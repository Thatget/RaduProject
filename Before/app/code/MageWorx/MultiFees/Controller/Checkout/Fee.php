<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Controller\Checkout;

class Fee extends AbstractFee
{
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \MageWorx\MultiFees\Helper\Fee
     */
    protected $helperFee;

    /**
     * @var \MageWorx\MultiFees\Helper\BillingAddressManager
     */
    protected $billingAddressManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Fee constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \MageWorx\MultiFees\Helper\Fee $helperFee
     * @param \MageWorx\MultiFees\Helper\BillingAddressManager $billingAddressManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \MageWorx\MultiFees\Helper\Fee $helperFee,
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
        $this->helperFee = $helperFee;
    }

    /**
     * @param array $feesPost
     * @return mixed
     */
    protected function prepareFeeData($feesPost)
    {
        foreach ($feesPost as $feeId => $feeData) {
            if (empty($feeData['options'])) {
                continue;
            }

            $options    = is_array($feeData['options']) ? $feeData['options'] : explode(',', $feeData['options']);
            $newOptions = [];
            foreach ($options as $option) {
                $newOptions[$option] = [];
            }

            $feesPost[$feeId]['options'] = $newOptions;
            $feesPost[$feeId]['type']    = $this->getRequest()->getPost('type');
        }
        return $feesPost;
    }
}
