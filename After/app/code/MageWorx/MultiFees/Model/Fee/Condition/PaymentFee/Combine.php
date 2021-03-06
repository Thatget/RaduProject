<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Fee\Condition\PaymentFee;

/**
 * Class Combine
 *
 * @package MageWorx\MultiFees\Model\Fee\Condition\PaymentFee
 *
 * @method Combine setType($string)
 */
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
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param Address $conditionAddress
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \MageWorx\MultiFees\Model\Fee\Condition\PaymentFee\Address $conditionAddress,
        array $data = []
    ) {
        $this->eventManager     = $eventManager;
        $this->conditionAddress = $conditionAddress;
        parent::__construct($context, $data);
        $this->setType('MageWorx\MultiFees\Model\Fee\Condition\PaymentFee\Combine');
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
                'value' => 'MageWorx\MultiFees\Model\Fee\Condition\PaymentFee\Address|' . $code,
                'label' => $label,
            ];
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions,
            [
                [
                    'value' => 'Magento\SalesRule\Model\Rule\Condition\Product\Found',
                    'label' => __('Product attribute combination'),
                ],
                [
                    'value' => 'MageWorx\MultiFees\Model\Fee\Condition\PaymentFee\Combine',
                    'label' => __('Conditions combination')
                ],
                ['label' => __('Cart Attribute'), 'value' => $attributes]
            ]
        );

        $additional = new \Magento\Framework\DataObject();
        $this->eventManager->dispatch('mageworx_fee_condition_combine', ['additional' => $additional]);
        $this->eventManager->dispatch('mageworx_payment_fee_condition_combine', ['additional' => $additional]);
        $additionalConditions = $additional->getConditions();
        if ($additionalConditions) {
            $conditions = array_merge_recursive($conditions, $additionalConditions);
        }

        return $conditions;
    }
}
