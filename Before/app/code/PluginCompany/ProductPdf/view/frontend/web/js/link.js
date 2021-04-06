define([
    'uiClass',
    'jquery',
    'underscore'
], function(Class, $, _) {
    return Class.extend({
        childUrls: [],
        mainUrl: '',
        node: null,
        initialize: function (config, node) {
            this._super();
            $(node)[config.insertMethod](config.linkSelector);

            this.childUrls = config.childProductUrls;
            this.mainUrl = this.mainProductUrl;
            this.node = $(node);

            var self = this;
            $( ".product-options-wrapper div" ).click(function() {
                self.setPdfLinkUrl();
            });
        },
        setPdfLinkUrl: function() {
            this.node.prop('href', this.getSelectedUrl());
        },
        getSelectedUrl: function() {
            var selected_options = {};
            $('div.swatch-attribute').each(function(k,v){
                var attribute_id    = jQuery(v).attr('attribute-id');
                var option_selected = jQuery(v).attr('option-selected');
                if(!attribute_id || !option_selected){ return;}
                selected_options[attribute_id] = option_selected;
            });

            var product_id_index = jQuery('[data-role=swatch-options]').data('mageSwatchRenderer').options.jsonConfig.index;
            var found_id;
            $.each(product_id_index, function(product_id,attributes){
                var productIsSelected = function(attributes, selected_options){
                    return _.isEqual(attributes, selected_options);
                }
                if(productIsSelected(attributes, selected_options)){
                    found_id = product_id
                }
            });
            if(found_id && typeof this.childUrls[found_id] != 'undefined') {
                return this.childUrls[found_id];
            }
            return this.mainUrl;
        }
    });
});