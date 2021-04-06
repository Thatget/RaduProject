define([
    'uiRegistry',
    'Magento_Checkout/js/model/quote',
], function (registry, quote) {
    'use strict';

    /**
     * Add update for total in Braintree PayPal form
     * after it was changed
     */
    var mixin = {

        /**
         * @returns {*}
         */
        initObservable: function () {
            var self = this;

            var result = this._super();
            quote.totals.subscribe(function () {
                if (self.isActive()) {
                    self.reInitPayPal();
                }
            });

            return result;
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});