<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Adminhtml\Fee\Edit\Tab;

use MageWorx\MultiFees\Model\ResourceModel\FeeAbstractResource;
use MageWorx\MultiFees\Model\AbstractFee;

/**
 * Fee add/edit form options tab
 */
class MainOptions extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Fee instance
     *
     * @var FeeAbstractResource
     */
    protected $fee;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $yesnoFactory;

    /**
     * @var \MageWorx\MultiFees\Model\Fee\Source\InputType
     */
    protected $inputTypeOptions;

    /**
     * @var \MageWorx\MultiFees\Model\Fee\Source\ApplyPerTypes
     */
    protected $applyPerTypes;

    /**
     * @var \MageWorx\MultiFees\Model\Fee\Source\CountPercentFrom
     */
    protected $countPercentFrom;

    /**
     * @var \MageWorx\MultiFees\Model\Fee\Source\AppliedTotals
     */
    protected $appliedTotalsOptions;

    /**
     * MainOptions constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory
     * @param \MageWorx\MultiFees\Model\Fee\Source\InputType $inputTypeOptions
     * @param \MageWorx\MultiFees\Model\Fee\Source\ApplyPerTypes $applyPerTypes
     * @param \MageWorx\MultiFees\Model\Fee\Source\CountPercentFrom $countPercentFrom
     * @param \MageWorx\MultiFees\Model\Fee\Source\AppliedTotals $appliedTotalsOptions
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory,
        \MageWorx\MultiFees\Model\Fee\Source\InputType $inputTypeOptions,
        \MageWorx\MultiFees\Model\Fee\Source\ApplyPerTypes $applyPerTypes,
        \MageWorx\MultiFees\Model\Fee\Source\CountPercentFrom $countPercentFrom,
        \MageWorx\MultiFees\Model\Fee\Source\AppliedTotals $appliedTotalsOptions,
        array $data = []
    ) {
        $this->yesnoFactory         = $yesnoFactory;
        $this->inputTypeOptions     = $inputTypeOptions;
        $this->applyPerTypes        = $applyPerTypes;
        $this->countPercentFrom     = $countPercentFrom;
        $this->appliedTotalsOptions = $appliedTotalsOptions;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Set attribute object
     *
     * @param Attribute $attribute
     * @return $this
     * @codeCoverageIgnore
     */
    public function setFeeObject($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Return attribute object
     *
     * @return Attribute
     */
    public function getFeeObject()
    {
        if (null === $this->fee) {
            return $this->_coreRegistry->registry('mageworx_multifees_fee');
        }

        return $this->fee;
    }

    /**
     * Preparing default form elements for editing attribute
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $feeObject = $this->getFeeObject();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $fieldset = $form->addFieldset('apply_fieldset', ['legend' => __('Fee Options')]);

        $fieldset->addField(
            'input_type',
            'select',
            [
                'name'   => 'input_type',
                'label'  => __('Input Type'),
                'title'  => __('Input Type'),
                'values' => $this->inputTypeOptions->toOptionArray(),
                'note'   => __('This setting defines how the fee will be displayed on the front-end.')
            ]
        );

        $fieldset->addField(
            'is_onetime',
            'select',
            [
                'name'   => 'is_onetime',
                'label'  => __('One-time'),
                'values' => $this->yesnoFactory->create()->toArray(),
                'value'  => '1',
                'note'   => __(
                        'This setting determines how the fee will depend on the number of items matching the conditions in the \'Apply to\' section.
If the "One-time" is set to "Yes", the fee will be applied only once despite of how many items/products are added to the cart, i.e. the fee\'s value won\'t be multiplied by the number of matching items. If it is set to "No", the the calculated fee\'s value will be multiplied by the number of matching items. '
                    )
                    . $this->getUserGuideLink()
            ]
        );

        $fieldset->addField(
            'apply_per',
            'select',
            [
                'name'   => 'apply_per',
                'label'  => __('Apply Per'),
                'title'  => __('Apply Per'),
                'values' => $this->getApplyPerValues(),
                'note'   => __('This setting define how the multiplier for the fee\'s value will be calculated. You can choose to calculate the fee based on the quantity of matching items or products, based on the X unit of weight (i.e. for each 2kg or 100g), based on X amount spent (i.e. for each 100$ spent).')
                    . $this->getUserGuideLink()
            ]
        );

        $fieldset->addField(
            'unit_count',
            'text',
            [
                'name'     => 'unit_count',
                'label'    => __('X Value'),
                'title'    => __('X Value'),
                'class'    => 'not-negative-amount decimal',
                'required' => true,
                'note'     =>__('This setting defines the X number used in the setting above.') . $this->getUserGuideLink()
            ]
        );

        $fieldset->addField(
            'use_bundle_qty',
            'select',
            [
                'name'   => 'use_bundle_qty',
                'label'  => __('Use Bundle Products Qty'),
                'values' => $this->yesnoFactory->create()->toArray(),
                'value'  => '1'
            ]
        );

        $fieldset->addField(
            'count_percent_from',
            'select',
            [
                'name'   => 'count_percent_from',
                'label'  => __('Count Percent From'),
                'title'  => __('Count Percent From'),
                'values' => $this->getCountPercentValues(),
                'note'   => __('For percent price type only') .  $this->getUserGuideLink()

            ]
        );

        $fieldset->addField(
            'applied_totals',
            'MageWorx\MultiFees\Data\Form\Element\FlexibleMultiselect',
            [
                'name'   => 'applied_totals[]',
                'label'  => __('Apply Fee To'),
                'title'  => __('Apply Fee To'),
                'values' => $this->appliedTotalsOptions->toOptionArray(),
                'note'   => __('For percent price type only')
            ]
        );

        $fieldset->addField(
            'min_amount',
            'text',
            [
                'name'     => 'min_amount',
                'label'    => __('Min amount'),
                'title'    => __('Min amount'),
                'note'   => __('For percent price type only. If a calculated fee\'s amount on the front-end is less ' .
                               'than this value, the min value is added.')
            ]
        );

        $form->addValues($feeObject->getData());

        $this->setChild(
            'form_after',
            $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Form\Element\Dependence'
            )->addFieldMap(
                'is_onetime',
                'is_onetime'
            )->addFieldMap(
                'apply_per',
                'apply_per'
            )->addFieldDependence(
                'apply_per',
                'is_onetime',
                '0'
            )->addFieldMap(
                'unit_count',
                'unit_count'
            )->addFieldDependence(
                'unit_count',
                'is_onetime',
                '0'
            )->addFieldMap(
                'count_percent_from',
                'count_percent_from'
            )->addFieldMap(
                'applied_totals',
                'applied_totals'
            )->addFieldDependence(
                'applied_totals',
                'count_percent_from',
                AbstractFee::FEE_COUNT_PERCENT_FROM_WHOLE_CART
            )
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return array
     */
    protected function getCountPercentValues()
    {
        $countPercentValues = $this->countPercentFrom->toOptionArray();
        if ($this->getFeeObject() instanceof \MageWorx\MultiFees\Api\Data\ProductFeeInterface) {
            foreach ($countPercentValues as $id => $data) {
                if ($data['value'] == AbstractFee::FEE_COUNT_PERCENT_FROM_WHOLE_CART) {
                    unset($countPercentValues[$id]);
                }
            }
        }

        return $countPercentValues;
    }

    /**
     * @return array
     */
    protected function getApplyPerValues()
    {
        $countPercentValues = $this->applyPerTypes->toOptionArray();
        if ($this->getFeeObject() instanceof \MageWorx\MultiFees\Api\Data\ProductFeeInterface) {
            foreach ($countPercentValues as $id => $data) {
                if ($data['value'] == AbstractFee::FEE_APPLY_PER_PRODUCT) {
                    unset($countPercentValues[$id]);
                }
            }
        }

        return $countPercentValues;
    }

    /**
     * Processing block html after rendering
     * Adding js block to the end of this block
     *
     * @param   string $html
     * @return  string
     */
    protected function _afterToHtml($html)
    {
        $jsScripts = $this->getLayout()->createBlock('Magento\Eav\Block\Adminhtml\Attribute\Edit\Js')->toHtml();

        return $html . $jsScripts;
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Options');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getUserGuideLink()
    {
        return '<br />' . __('For more details see') .
            ' <a href="https://support.mageworx.com/manuals/multifees/#multi-fees-management" target="_blank"><span>'
            . __('User Guide') . '</span></a>';
    }
}
