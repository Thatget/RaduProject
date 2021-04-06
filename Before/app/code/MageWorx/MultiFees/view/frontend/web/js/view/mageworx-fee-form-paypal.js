/*global define*/
define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/form',
    'MageWorx_MultiFees/js/model/fee',
    'MageWorx_MultiFees/js/action/apply-fees-paypal',
    'MageWorx_MultiFees/js/model/fee-messages',
    'mage/translate',
    'underscore'
], function (
    $,
    ko,
    Component,
    fee,
    applyFeesAction,
    messageContainer,
    $t,
    _
) {
    'use strict';

    var isLoading = ko.observable(false);

    return Component.extend({

        isLoading: isLoading,

        initialize: function () {
            this._super();
            return this;
        },

        /**
         * Switch container visibility
         *
         * @param visibility
         */
        containerVisibility: function (visibility) {
            var $container,
                type = Number(this.feeType);

            switch (type) {
                case 1:
                    $container = $('#block-fee');
                    break;
                case 3:
                    $container = $('#block-shipping-fee');
                    break;
                case 2:
                    $container = $('#block-payment-fee');
                    break;
                default:
                    $container = null;
            }

            if (!$container) {
                return;
            }

            if (visibility) {
                $container.show();
            } else {
                $container.hide();
            }
        },

        initObservable: function () {
            var self = this,
                $shippingMethodInput = $('#shipping-method');

            this._super()
                .observe('isVisible isDisplayTitle isDisplayed applyOnClick');

            self.isDisplayed.subscribe(function (value) {
                if (value) {
                    self.containerVisibility(true);
                } else {
                    self.containerVisibility(false);
                }
            });

            self.elems.subscribe(function (value) {
                self.getChild(self.feesFieldsetName).elems.subscribe(function (value) {
                    self.isDisplayed(value.length > 0);
                });
            });

            $shippingMethodInput.on('change', function () {
                setTimeout(function(){
                    location.reload();
                }, 500);
            });

            self.isDisplayed(false);
            self.isDisplayTitle(true);
            self.isVisible(true);
            self.applyOnClick(fee.allData().applyOnClick);

            return this;
        },

        onSubmit: function () {
            var feeData = {};
            feeData = _.extend(feeData, this.source[this.dataName], {'type': this.feeType});
            applyFeesAction(feeData, isLoading, 'mageworx-fee-form');

            return this;
        }
    });
});
