/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/model/authentication-popup',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'uiRegistry',
    'Magento_Checkout/js/action/set-shipping-information',
], function ($, authenticationPopup, customerData, confirmation, $t, registry, setShippingInformationAction) {
    'use strict';

    var updateShippingInsurance = function (formData, index) {
        $('[name^=mageworxShippingFeeForm]').each(function () {
            var divName = $(this).attr('name');
            if (divName == 'mageworxShippingFeeForm.' +index ) {
                $(this).find('input[type=checkbox]').click();
            }
        });
        setShippingInformationAction().done(
            function () {
                location.href = window.checkoutConfig.checkoutUrl;
            }
        );
    }
    return function (config, element) {
        $(element).click(function (event) {
            var cart = customerData.get('cart'),
                customer = customerData.get('customer');

            event.preventDefault();

            if (!customer().firstname && cart().isGuestCheckoutAllowed === false) {
                authenticationPopup.showModal();

                return false;
            }
            $(element).attr('disabled', true);

            var source = registry.get('checkoutProvider');
            var formData = source.get('mageworxShippingFeeForm');
            if (typeof formData == 'undefined') {
                location.href = config.checkoutUrl;
            }
            _.each(formData, function (optionVal, index) {
                if (index != 'type') {
                    // if (_.isEmpty(optionVal)) {
                    //     confirmation({
                    //         title: $t('ALERT'),
                    //         content: $t('Shipping Insurance will cover you for any loss or damage during transit. Select “YES” for peace of mind.'),
                    //         modalClass: 'see-detail-modal',
                    //         actions: {
                    //             confirm: function () {
                    //                 updateShippingInsurance(formData, index);
                    //                 return false;
                    //             },
                    //             cancel: function () {
                    //                 setShippingInformationAction().done(
                    //                     function () {
                    //                         location.href = config.checkoutUrl;
                    //                     }
                    //                 );

                    //             }
                    //         },
                    //         buttons: [
                    //             {
                    //                 text: $.mage.__('YES'),
                    //                 class: 'action primary action-accept customconfirmyes',
                    //                 click: function (event) {
                    //                     this.closeModal(event, true);
                    //                 }
                    //             },
                    //             {
                    //                 text: $.mage.__('NO'),
                    //                 class: 'action-secondary action-dismiss customconfirmno',
                    //                 click: function (event) {
                    //                     this.closeModal(event);
                    //                 }
                    //             }
                    //         ]
                    //     });
                    // } else {
                    //     setShippingInformationAction().done(
                    //         function () {
                    //             location.href = config.checkoutUrl;
                    //         }
                    //     );
                    // }
                setShippingInformationAction().done(
                            function () {
                                location.href = config.checkoutUrl;
                            }
                        );
                }
            });
        });
    };
});
