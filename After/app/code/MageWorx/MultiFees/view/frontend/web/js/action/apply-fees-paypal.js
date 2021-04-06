/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

/*global define,alert*/
define(
    [
        'ko',
        'jquery',
        'MageWorx_MultiFees/js/model/fee',
        'Magento_Checkout/js/model/error-processor',
        'MageWorx_MultiFees/js/model/fee-messages',
        'mage/storage',
        'mage/translate'
    ],
    function (
        ko,
        $,
        fee,
        errorProcessor,
        messageContainer,
        storage,
        $t
    ) {
        'use strict';
        return function (feeData, isLoading, formId) {
            var successMessage = $t('Fees were successfully applied.'),
                errorMessage = $t('Could not apply additional fee(s)'),
                widget = $("#order-review-form").data("mageOrderReview"),
                submitUrl;

            switch (Number(feeData['type'])) {
                case 4:
                    submitUrl = window.mageworxProductFeeInfo.url;
                    break;
                case 1:
                case 2:
                case 3:
                default:
                    submitUrl = fee.allData().url;
                    break;
            }

            widget._ajaxBeforeSend();

            $.ajax({
                url: submitUrl,
                data: feeData,
                type: 'post',
                dataType: 'json'
            }).done(function (response) {
                if (response) {
                    location.reload();
                } else {
                    messageContainer.addErrorMessage({'message': errorMessage});
                }
            }).fail(
                function (response) {
                    widget._ajaxComplete();
                    console.log(response);
                    messageContainer.addErrorMessage({'message': errorMessage});
                }
            );

            /**
             * Updating totals
             */
            function updateTotals() {
                var $shippingMethod = $('#shipping-method'),
                    addShippingSelect = false,
                    isShippingSubmitFormOriginalValue = widget.isShippingSubmitForm;

                try {
                    if (addShippingSelect) {
                        $('#shipping-method-form').prepend(
                            $('<select name="shipping_method" id="shipping-method">' +
                                '<option value="' + fee.allData().defaultShippingMethod + '" selected="selected">' +
                                '</option>' +
                                '</select>'
                            )
                        );
                    }

                    widget.isShippingSubmitForm = true;
                    widget._submitUpdateOrder(
                        $(widget.options.shippingSubmitFormSelector).prop('action'),
                        widget.options.updateContainerSelector
                    );
                    widget.isShippingSubmitForm = isShippingSubmitFormOriginalValue;

                    if (addShippingSelect) {
                        $shippingMethod.remove();
                    }
                } catch (e) {
                    console.log(e);
                }
            }
        };
    }
);
