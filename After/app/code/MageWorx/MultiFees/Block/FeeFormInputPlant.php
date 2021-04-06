<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use MageWorx\MultiFees\Api\Data\FeeInterface;
use MageWorx\MultiFees\Block\FeeFormInput\FeeFormInputRenderInterface;
use MageWorx\MultiFees\Model\AbstractFee;
use MageWorx\MultiFees\Helper\ProductFeePrice as HelperProductFeePrice;

/**
 * Class FeeFormInputFactory
 * Factory where you can create the form input for the corresponding fee by its type
 *
 * @package MageWorx\MultiFees\Block
 */
class FeeFormInputPlant
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array|FeeFormInputRenderInterface[]
     */
    private $feeRendererByItsType;

    /**
     * @var HelperProductFeePrice
     */
    protected $helperProductFeePrice;

    /**
     * FeeFormInputPlant constructor.
     *
     * @param HelperProductFeePrice $helperProductFeePrice
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $feeRendererByItsType
     */
    public function __construct(
        HelperProductFeePrice $helperProductFeePrice,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $feeRendererByItsType = []
    ) {
        $this->helperProductFeePrice = $helperProductFeePrice;
        $this->objectManager         = $objectManager;
        $this->feeRendererByItsType  = $feeRendererByItsType;
    }

    /**
     * Create form input instance
     *
     * @param FeeInterface $fee
     * @param array $data
     * @return FeeFormInputRenderInterface
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(FeeInterface $fee, array $data = [])
    {
        if (empty($this->feeRendererByItsType[$fee->getInputType()])) {
            throw new NoSuchEntityException(__('There no render for the fee type %1', $fee->getInputType()));
        }

        $instanceName = $this->feeRendererByItsType[$fee->getInputType()];
        $data['fee']  = $fee;

        /** @var $render FeeFormInputRenderInterface */
        $render = $this->objectManager->create(
            $instanceName,
            $data
        );

        if (!$render instanceof FeeFormInputRenderInterface) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    '%1 doesn\'t implement \MageWorx\MultiFees\Api\FeeFormInputRenderInterface',
                    [$instanceName]
                )
            );
        }

        if ($fee->getType() == AbstractFee::PRODUCT_TYPE) {
            $render->setHelperPrice($this->helperProductFeePrice);
        }

        return $render;
    }
}
