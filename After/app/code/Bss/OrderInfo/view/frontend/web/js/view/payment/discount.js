/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'mage/translate'
], function ($, ko, Component, urlBuilder,quote, storage, $t) {
    'use strict';

    var backorderContent = ko.observable('asdasdasd');

    return Component.extend({
        defaults: {
            template: 'Bss_OrderInfo/payment/discount'
        },
        backorderContent: backorderContent,
            shippingInsurance: ko.observable(''),
        initialize: function (config) {
            var self = this;
            self._super();

            var serviceUrl;
            serviceUrl = urlBuilder.build('bss_orderinfo/backorder/comment', {});
            storage.get(
                serviceUrl, false
            ).done(function (response) {
                backorderContent(response.message);
            }).fail(function (response) {
            });
            quote.totals.subscribe(function (newValue) {
                if (quote.shippingMethod() && quote.shippingMethod().carrier_code == 'matrixrate') {
                    self.updateShippingInsurance();
                } else {
                    self.shippingInsurance('');
                }
            });
            return this;
        },		
        updateShippingInsurance: function () {
        var totalSegments = quote.getTotals()().total_segments;
        var mageworxFee = _.where(totalSegments, {code: "mageworx_fee"});
        var mageworxFeeValue = 0;
        if (mageworxFee.length) {
            mageworxFeeValue = _.first(mageworxFee).value;
        }
        if (mageworxFeeValue > 0) {
            this.shippingInsurance($t('**Transit Insurance Purchased (Loss or Damage is covered)'));
        } else {
            this.shippingInsurance($t('**Transit Insurance NOT Purchased (Loss or Damage is not covered)'));
        }
    },
        getItems: function () {
            var items = quote.getItems();
            return items;
        },
        getFreeProduct: function (item) {
            var html = '- ',
                product = item.product;
            html += product.name;
            html += ' (';
            html += $t('Free Shipping');
            html += ')';
            return html;
        },
        hasProductFreeShipping: function () {
        var hasProductFreeShipping = false,
            items = this.getItems(),
            total = quote.getTotals()();
        if (_.isUndefined(window.checkoutConfig.bss_webshopapps_matrixrate) ||
            (!_.isUndefined(window.checkoutConfig.bss_webshopapps_matrixrate) &&
                total.subtotal_incl_tax >= window.checkoutConfig.bss_webshopapps_matrixrate)
        ) {
            _.each(items, function (item) {
                if (item.product.free_shipping_value != 'No Free Shipping') {
                    hasProductFreeShipping = true;
                    return false;
                }
            });
        }
        return hasProductFreeShipping;
    }
    });
});
