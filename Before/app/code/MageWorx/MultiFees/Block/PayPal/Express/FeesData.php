<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\PayPal\Express;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use MageWorx\MultiFees\Helper\Data as Helper;

/**
 * Class FeesData
 */
class FeesData extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * FeesData constructor.
     *
     * @param Template\Context $context
     * @param Helper $helper
     * @param Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Helper $helper,
        Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Get specified fee data
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFeeData()
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        $shippingMethodCode = $quote->getShippingAddress()->getShippingMethod();

        $result = [
            'url'          => $this->getUrl('multifees/checkout/fee'),
            'applyOnClick' => $this->helper->isApplyOnClick(),
            'defaultShippingMethod' => $shippingMethodCode
        ];

        return $result;
    }
}