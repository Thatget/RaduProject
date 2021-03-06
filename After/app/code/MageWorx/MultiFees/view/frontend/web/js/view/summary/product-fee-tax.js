define(
    [
        'ko',
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals'
    ],
    function (ko, Component, quote, priceUtils, totals) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'MageWorx_MultiFees/summary/product-fee-tax'
            },

            totals: quote.getTotals(),

            isDisplayed: function () {
                if (this.isFullMode()) {
                    var price = 0;
                    if (this.totals() && totals.getSegment('mageworx_product_fee_tax')) {
                        price = totals.getSegment('mageworx_product_fee_tax').value;
                        if (price > 0) {
                            return true;
                        }
                    }
                }
                return false;
            },

            getValue: function () {
                var price = 0;
                if (this.totals() && totals.getSegment('mageworx_product_fee_tax')) {
                    price = totals.getSegment('mageworx_product_fee_tax').value;
                }
                return this.getFormattedPrice(price);
            },

            getBaseValue: function () {
                var price = 0;
                if (this.totals()) {
                    price = this.totals().base_fee;
                }
                return priceUtils.formatPrice(price, quote.getBasePriceFormat());
            },

            formatPrice: function (price) {
                return this.getFormattedPrice(price);
            },

            getDetails: function () {
                var feeSegment = totals.getSegment('mageworx_product_fee_tax');
                return feeSegment && feeSegment.extension_attributes ?
                    feeSegment.extension_attributes.mageworx_product_fee_details :
                    [];
            },

            formatToFixed: function (value) {
                return Number(value).toFixed(2);
            }
        });
    }
);