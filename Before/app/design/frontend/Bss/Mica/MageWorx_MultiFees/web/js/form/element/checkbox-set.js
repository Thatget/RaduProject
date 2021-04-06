define([
    'Magento_Ui/js/form/element/checkbox-set',
    'uiRegistry',
    'jquery',
    'Bss_MageWorxMultiFees/js/model/shipping-fee-service',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Ui/js/modal/confirm',
    'Magento_Checkout/js/model/quote'
], function (Abstract, registry, $, shippingFee, defaultTotal, cartCache, confirmation, quote) {
    'use strict';

    return Abstract.extend({
        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            var form;
            try {
                form = this.containers[0].containers[0] ?
                    this.containers[0].containers[0] :
                    registry.get('index = mageworx-fee-form-container');
            } catch (e) {
                form = registry.get('index = mageworx-fee-form-container');
            }
            if (this.value().length) {
                shippingFee.hasValue(true);
            } else {
                shippingFee.hasValue(false);
            }
            if (this.applyOnClick) {
                form.onSubmit();
            }
            if ($('.checkout-cart-index').length) {
                // if (!shippingFee.hasValue()) {
                //     var self = this;
                //     confirmation({
                //             title: $.mage.__('ALERT'),
                //             content: $.mage.__('Shipping Insurance will cover you for any loss or damage during transit. Select “YES” for peace of mind.'),
                //             modalClass: 'see-detail-modal',
                //             actions: {
                //                 confirm: function () {
                //                 },
                //                 cancel: function () {
                //                 }
                //             },
                //             buttons: [
                //                 {
                //                     text: $.mage.__('YES'),
                //                     class: 'action primary action-accept customconfirmyes',
                //                     click: function (event) {
                //                         self.reset();
                //                         shippingFee.hasValue(true);
                //                         form.onSubmit();
                //                         this.closeModal(event);
                //                     }
                //                 },
                //                 {
                //                     text: $.mage.__('NO'),
                //                     class: 'action-secondary action-dismiss customconfirmno',
                //                     click: function (event) {
                //                          form.onSubmit();
                //                         this.closeModal(event);
                //                     }
                //                 }
                //             ]
                //         });
                // } else {
                //     form.onSubmit();
                // }
                form.onSubmit();
            }
            if ($('.checkout-index-index').length) {
                if (!shippingFee.hasValue()) {
                    var self = this;
                    confirmation({
                            title: $.mage.__('ALERT'),
                            content: $.mage.__('Shipping Insurance will cover you for any loss or damage during transit. Select “YES” for peace of mind.'),
                            modalClass: 'see-detail-modal',
                            actions: {
                                confirm: function () {
                                },
                                cancel: function () {
                                }
                            },
                            buttons: [
                                {
                                    text: $.mage.__('YES'),
                                    class: 'action primary action-accept customconfirmyes',
                                    click: function (event) {
                                        self.reset();
                                        shippingFee.hasValue(true);
                                        form.onSubmit();
                                        cartCache.set('totals',null);
                                        defaultTotal.estimateTotals(quote.shippingAddress());
                                        this.closeModal(event);
                                    }
                                },
                                {
                                    text: $.mage.__('NO'),
                                    class: 'action-secondary action-dismiss customconfirmno',
                                    click: function (event) {                                        
                                        form.onSubmit();
                                        cartCache.set('totals',null);
                                        defaultTotal.estimateTotals(quote.shippingAddress());
                                        this.closeModal(event);
                                    }
                                }
                            ]
                        });
                } else {
                    form.onSubmit();
                    cartCache.set('totals',null);
                    defaultTotal.estimateTotals(quote.shippingAddress());
                }
            }
        }
    });
});

