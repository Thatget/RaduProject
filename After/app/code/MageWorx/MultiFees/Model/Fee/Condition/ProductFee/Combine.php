<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Fee\Condition\ProductFee;

class Combine extends \Magento\Rule\Model\Condition\Combine
{
    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \MageWorx\MultiFees\Model\Fee\Condition\Address
     */
    protected $conditionAddress;

    /**
     * Combine constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param Address $conditionAddress
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \MageWorx\MultiFees\Model\Fee\Condition\ProductFee\Address $conditionAddress,
        array $data = []
    ) {
        $this->eventManager     = $eventManager;
        $this->conditionAddress = $conditionAddress;
        parent::__construct($context, $data);
        $this->setType('MageWorx\MultiFees\Model\Fee\Condition\ProductFee\Combine');
    }

    /**
     * Get new child select options
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $addressAttributes = $this->conditionAddress->loadAttributeOptions()->getAttributeOption();

        $attributes = [];
        foreach ($addressAttributes as $code => $label) {
            $attributes[] = [
                'value' => 'MageWorx\MultiFees\Model\Fee\Condition\ProductFee\Address|' . $code,
                'label' => $label,
            ];
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions,
            [
                [
                    'value' => 'MageWorx\MultiFees\Model\Fee\Condition\ProductFee\Combine',
                    'label' => __('Conditions combination')
                ],
                ['label' => __('Product Attribute'), 'value' => $attributes]
            ]
        );

        $additional = new \Magento\Framework\DataObject();
        $this->eventManager->dispatch('mageworx_fee_condition_combine', ['additional' => $additional]);
        $this->eventManager->dispatch('mageworx_product_fee_condition_combine', ['additional' => $additional]);
        $additionalConditions = $additional->getConditions();
        if ($additionalConditions) {
            $conditions = array_merge_recursive($conditions, $additionalConditions);
        }

        return $conditions;
    }
}
