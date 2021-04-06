/*global define*/
define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/form',
    'MageWorx_MultiFees/js/model/product-fee'
], function (
    $,
    ko,
    Component,
    fee
) {
    'use strict';

    var isLoading = ko.observable(false);

    return Component.extend({
        initialize: function () {
            this._super();
            return this;
        },

        isLoading: isLoading,

        isDisplayTitle: function () {
            return fee.allData().is_display_title;
        },

        isDisplayed: function () {
            return fee.allData().is_enable;
        }
    });
});
