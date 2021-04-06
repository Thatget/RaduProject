
require([
'jquery'
], function($){
	if(jQuery(window).width()>=768) {
		var imgLink = jQuery('.category-blocks>img.image-wraper').attr('src');
		jQuery('.category-blocks .offer-inner .col').mouseenter(function(e){
			var element = jQuery(e.currentTarget).find('>a>img');
			var src = element.attr('src');
		    element.attr('data-src',src);
			element.attr('src',imgLink)
			
		})

		jQuery('.category-blocks .offer-inner .col').mouseleave(function(e){
			var element = jQuery(e.currentTarget).find('>a>img');
			var src = element.attr('src');
		    element.attr('src',element.attr('data-src'));
		})
	}
	
});