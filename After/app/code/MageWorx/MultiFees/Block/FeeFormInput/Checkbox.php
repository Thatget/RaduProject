<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\FeeFormInput;

class Checkbox extends AbstractInput
{
    /**
     * Render form input component for the fee
     *
     * @return array
     */
    public function render()
    {
        $isApplyOnClick                  = $this->helper->isApplyOnClick();
        $scope                           = $this->scope;
        $component                       = [];
        $component['component']          = $this->getCheckboxComponent();
        $component['config']             = [
            'customScope' => $scope,
            'template'    => 'MageWorx_MultiFees/form/field',
            'elementTmpl' => 'MageWorx_MultiFees/form/element/checkbox-set'
        ];
        $component['dataScope']          = $this->getDataScope();
        $component['label']              = $this->fee->getTitle();
        $component['provider']           = 'checkoutProvider';
        $component['visible']            = true;
        $component['validation']         = [];
        $component['applyOnClick']       = $isApplyOnClick;
        $component['isVisibleInputType'] = static::VISIBLE_TYPE;
        $component['multiple']           = true;
        $component['name']               = $this->getFeeName();
        $selectedOptions                 = [];

        $options = [];

        if ($this->fee->getRequired()) {
            $component['validation']['required-entry'] = 'true';
        }

        $defaultSelectedOptions = [];
        foreach ($this->fee->getOptions() as $option) {
            if (!empty($this->details[$this->fee->getId()]['options'][$option->getId()])) {
                $selectedOptions[] = $option->getId();
            }

            $optionTitle = $option->getTitle() . ' - '
                . $this->helperPrice->getOptionFormatPrice($option, $this->fee);
            $options[]   =
                [
                    'label' => $optionTitle,
                    'value' => $option->getId(),
                ];

            if ($option->getIsDefault()) {
                $defaultSelectedOptions[] = $option->getId();
            }
        }

        if (empty($selectedOptions)) {
            $selectedOptions = $defaultSelectedOptions;
        }

        $component['notice']    = $this->fee->getDescription();
        $component['options']   = $options;
        $component['sortOrder'] = $this->fee->getSortOrder();
        $component['value']     = $selectedOptions;
        $component['feeType']   = $this->fee->getType();

        return $component;
    }
}
