/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

define(
    [
        'ko'
    ],
    function (ko) {
        'use strict';
        var tempProductFeeData = window.mageworxProductFeeInfo;
        var allData = ko.observable(tempProductFeeData);

        return {
            allData: allData,
            getData: function () {
                return allData;
            },

            setData: function (data) {
                allData(data);
            },

            validate: function () {
                //if (this.allData().is_enable) {
                //return this.allData().is_valid;
                //}
                return true;
            }
        }
    }
);

