<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\FeeFormInput;

use MageWorx\MultiFees\Model\AbstractFee;

class Hidden extends AbstractInput
{
    /**
     * Render form input component for the fee
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render()
    {
        $isApplyOnClick                  = $this->helper->isApplyOnClick();
        $scope                           = $this->scope;
        $component                       = [];
        $component['component']          = $this->getHiddenComponent();
        $component['config']             = [
            'customScope' => $scope,
            'template'    => $this->getHiddenFieldTemplate(),
            'elementTmpl' => 'MageWorx_MultiFees/form/element/text_hidden_fee'
        ];
        $component['dataScope']          = $this->getDataScope();
        $component['label']              = $this->fee->getTitle();
        $component['provider']           = 'checkoutProvider';
        $component['visible']            = true;
        $component['validation']         = [];
        $component['applyOnClick']       = $isApplyOnClick;
        $component['isVisibleInputType'] = static::INVISIBLE_TYPE;
        $component['name']               = $this->getFeeName();

        $options = [];

        if ($this->fee->getRequired()) {
            $component['validation']['required-entry'] = 'true';
        } else {
            $options[] =
                [
                    'label' => __('None'),
                    'value' => 0
                ];
        }

        foreach ($this->fee->getOptions() as $option) {
            if (!empty($this->details[$this->fee->getId()]['options'][$option->getId()])) {
                $component['value'] = $option->getId();
            }

            $optionLabel = $option->getTitle() . ' - ' .
                $this->helperPrice->getOptionFormatPrice($option, $this->fee);

            $options[] =
                [
                    'label' => $optionLabel,
                    'value' => $option->getId()
                ];

            if ($option->getIsDefault()) {
                $defaultOption = $option;
                $defaultOption->setLabel($optionLabel);
                if ($this->fee->getType() == AbstractFee::PRODUCT_TYPE) {
                    $component['optionLabel'] = $defaultOption->getLabel();
                }
            }
        }

        if (empty($component['value'])) {
            if (isset($defaultOption)) {
                $component['value'] = $defaultOption->getId();
            } else {
                $component['value'] = !empty($options[0]['value']) ? $options[0]['value'] : null;
            }
        }

        $component['notice']    = $this->fee->getDescription();
        $component['options']   = $options;
        $component['sortOrder'] = $this->fee->getSortOrder();
        $component['feeType']   = $this->fee->getType();

        return $component;
    }

    /**
     * @return string
     */
    protected function getHiddenFieldTemplate()
    {
        if ($this->fee->getType() == AbstractFee::PRODUCT_TYPE) {
            return 'MageWorx_MultiFees/form/hidden_field_product_fee';
        }
        return 'MageWorx_MultiFees/form/hidden_field';
    }


    /**
     * @return string
     */
    protected function getHiddenComponent()
    {
        if ($this->fee->getType() == AbstractFee::PRODUCT_TYPE) {
            return 'MageWorx_MultiFees/js/form/element/product-hidden';
        }

        return 'MageWorx_MultiFees/js/form/element/hidden';
    }
}
