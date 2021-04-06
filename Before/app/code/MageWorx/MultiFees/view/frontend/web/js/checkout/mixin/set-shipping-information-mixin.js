/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'mage/utils/wrapper',
    'uiRegistry',
    'MageWorx_MultiFees/js/action/apply-fees',
    'MageWorx_MultiFees/js/model/fee-messages'
], function ($, wrapper, registry, applyFeesAction, messageContainer) {
    'use strict';

    return function (setShippingInformationAction) {

        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var source = registry.get('checkoutProvider');
            source.set('params.invalid', false);
            source.trigger('mageworxShippingFeeForm.data.validate');

            if (!source.get('params.invalid')) {
                var formData = source.get('mageworxShippingFeeForm');
                if (formData) {
                    formData['type'] = 3;
                    applyFeesAction(formData, function(){}, 'mageworx-shipping-fee-form');
                }
            } else {
                messageContainer.addErrorMessage({'message': errorMessage});
            }

            return originalAction();
        });
    };
});
