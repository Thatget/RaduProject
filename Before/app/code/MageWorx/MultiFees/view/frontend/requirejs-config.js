/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-shipping-information': {
                'MageWorx_MultiFees/js/checkout/mixin/set-shipping-information-mixin': true
            },
            'Magento_Braintree/js/view/payment/method-renderer/paypal': {
                'MageWorx_MultiFees/js/mixin/paypal-renderer-mixin': true
            },
            'Bss_OneStepCheckout/js/action/set-shipping-information': {
                'MageWorx_MultiFees/js/checkout/mixin/set-shipping-information-mixin': true
            }
        }
    }
};