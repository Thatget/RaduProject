require(
[
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
],
    function(
        $,
        modal
    ) {
        var title = $('#webform-price-beat .webforms .form .fieldset-1 .legend span').text();
        var elements = $('.column.main>.messages');
        $.each(elements, function(key, element) {
            $(element).find('~ .webform-container')[0].prepend(element);
        })
        $('.webforms .form .actions-toolbar').append($.mage.__('<span class="required">* Required Fields<span>'));

        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: title,
        };

        var popup = modal(options, $('#webform-price-beat'));
        var modalHeader = $('#webform-price-beat').parent().parent().find('.modal-header');
        var buttonClose = $('#webform-price-beat').parent().parent().find('.modal-header .action-close');
        $('#webform-price-beat').parent().parent().find('.modal-header .action-close').hide();
        $('#webform-price-beat').prepend('<button class="btn-close"></button>');
        $('#webform-price-beat >.webforms').prepend(modalHeader);
        $('#webform-price-beat >.btn-close').click(function(){
            console.log('click')
            $('#webform-price-beat .webforms .modal-header .action-close').trigger('click');
        });
        $('.action.price-best').click(function(){
            $('#webform-price-beat').modal('openModal');
        })
        
    }
);
