/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/url',
    'mage/storage'
], function ($, ko, Component, urlBuilder, storage) {
    'use strict';

    var backorderContent = ko.observable('asdasdasd');

    return Component.extend({
        defaults: {
            template: 'Bss_OrderInfo/payment/discount'
        },
        backorderContent: backorderContent,
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
            return this;
        }
    });
});
