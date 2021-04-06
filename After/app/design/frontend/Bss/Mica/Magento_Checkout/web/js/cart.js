require([
'jquery'
], function($){
	$('.page-title-wrapper .action.checkout').click(function(){
		$('.cart-summary .action.checkout').trigger('click');
	});
	$('#shopping-cart-table .item-info .col.qty .control.edit').click(function(e){
		var productId = $(e.currentTarget).attr('data-product-id');
		var selector = '#gift-options-cart-item-'+ productId + ' + .action-edit';
		var link = $(selector).attr('href');
		$(location).attr('href', link)
	});
	$('#shopping-cart-table .item-info .action.remove').click(function(e){
		var productId = $(e.currentTarget).attr('data-product-id');
		var selector = '#gift-options-cart-item-'+ productId + ' ~ .action-delete';
		$(selector).trigger('click');
	})
});
