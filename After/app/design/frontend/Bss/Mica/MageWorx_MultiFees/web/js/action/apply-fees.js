/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

/*global define,alert*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'MageWorx_MultiFees/js/model/fee',
        'Magento_Checkout/js/model/error-processor',
        'MageWorx_MultiFees/js/model/fee-messages',
        'mage/storage',
        'mage/translate',
        'Magento_Checkout/js/action/get-payment-information',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/cart/totals-processor/default'
    ],
    function (
        ko,
        $,
        quote,
        urlManager,
        fee,
        errorProcessor,
        messageContainer,
        storage,
        $t,
        getPaymentInformationAction,
        totals,
        stepnav,
        totalsProcessor
    ) {
        'use strict';
        return function (feeData, isLoading, formId) {
            var successMessage = $t('Fees were successfully applied.'),
                errorMessage = $t('Could not apply additional fee(s)');

            $.ajax({
                url: fee.allData().url,
                data: feeData,
                type: 'post',
                dataType: 'json'
            })
                .done(function (response) {
                    if (response) {
                        if (response.reload) {
                            location.reload();
                        } else {
                            updateTotals();
                        }
                    } else {
                        isLoading(false);
                        totals.isLoading(false);
                        messageContainer.addErrorMessage({'message': errorMessage});
                    }
                })
                .fail(
                    function (response) {
                        isLoading(false);
                        totals.isLoading(false);
                        messageContainer.addErrorMessage({'message': errorMessage});
                    }
                );

            function updateTotals() {
                isLoading(false);

                // Reload totals if all done (cart page only)
                if (_.isEmpty(stepnav.steps())) {
                    try {
                        require(
                            [
                                'Magento_Checkout/js/model/cart/cache',
                                'Magento_Checkout/js/model/cart/totals-processor/default'
                            ],
                            function (cartCache, totalsProcessor) {
                                cartCache.clear('cartVersion');
                                totalsProcessor.estimateTotals(quote.shippingAddress());
                            }
                        );
                    } catch (e) {
                        totalsProcessor.estimateTotals(quote.shippingAddress());
                    }

                    if (!require.defined('Magento_Checkout/js/model/cart/cache')) {
                        totalsProcessor.estimateTotals(quote.shippingAddress());
                    }
                }

                if (stepnav.getActiveItemIndex()) {
                    var deferred = $.Deferred();
                    totals.isLoading(true);
                    getPaymentInformationAction(deferred);
                    $.when(deferred).done(function () {
                        totals.isLoading(false);
                    });
                }
            }
        };
    }
);
