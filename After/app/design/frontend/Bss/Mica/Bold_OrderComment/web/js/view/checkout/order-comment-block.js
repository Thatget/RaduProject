define(
    [
        'jquery',
        'uiComponent',
        'knockout',
        'Magento_Checkout/js/model/quote',
        'underscore',
        'mage/translate'
    ],
    function ($, Component, ko, quote, _, $t) {
        'use strict';

        ko.extenders.maxOrderCommentLength = function (target, maxLength) {
            var timer;
            var result = ko.computed({
                read: target,
                write: function (val) {
                    if (maxLength > 0) {
                        clearTimeout(timer);
                        if (val.length > maxLength) {
                            var limitedVal = val.substring(0, maxLength);
                            if (target() === limitedVal) {
                                target.notifySubscribers();
                            } else {
                                target(limitedVal);
                            }
                            result.css("_error");
                            timer = setTimeout(function () { result.css(""); }, 800);
                        } else {
                            target(val);
                            result.css("");
                        }
                    } else {
                        target(val);
                    }
                }
            }).extend({ notify: 'always' });
            result.css = ko.observable();
            result(target());
            return result;
        };

        return Component.extend({
            defaults: {
                template: 'Bold_OrderComment/checkout/order-comment-block'
            },

            shippingInsurance: ko.observable(''),

            initialize: function() {
                this._super();
                var self = this;
                this.comment = ko.observable("").extend({maxOrderCommentLength: this.getMaxLength()});
                this.remainingCharacters = ko.computed(function(){
                    return self.getMaxLength() - self.comment().length;
                });
                quote.totals.subscribe(function (newValue) {
                    if (quote.shippingMethod() && quote.shippingMethod().carrier_code == 'matrixrate') {
                        self.updateShippingInsurance();
                    } else {
                        self.shippingInsurance('');
                    }
                });
            },
            hasMaxLength: function() {
                return window.checkoutConfig.max_length > 0;
            },
            getMaxLength: function () {
                return window.checkoutConfig.max_length;
            },
            getInitialCollapseState: function() {
                return window.checkoutConfig.comment_initial_collapse_state;
            },
            isInitialStateOpened: function() {
                return this.getInitialCollapseState() === 1
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
    }
);
