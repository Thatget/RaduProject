require([
	'jquery', // jquery Library
	'jquery/ui', // Jquery UI Library
	'mage/translate' // Magento text translate (Validation message translte as per language)
	], function($){
		if(jQuery(window).width()>=771) {
			var elements = jQuery('.level0.category-item');
			jQuery.each(elements, function(key, parent){
				var categoryUrl  = jQuery(parent).find('> a').attr('href'),
				category 		 = jQuery(parent).find('> a span').not('.ui-menu-icon').text(),
				menu 			 = jQuery(parent).find('> ul');
				var categoryLink = jQuery('<a>')
	                .attr('href', categoryUrl)
	                .text($.mage.__('View All ') + category);

	            var categoryParent = jQuery('<li>')
	                .addClass('ui-menu-item all-categories')
	                .html(categoryLink);
	                menu.prepend(categoryParent);
	            if (menu.find('>.all-categories').length === 0) {
	                menu.prepend(categoryParent);
	            }
			});
		}
		if(jQuery(window).width() < 771)
		{
			jQuery('.level1.category-item').each(function(key, parent){
				var categoryUrl  = jQuery(parent).find('> a').attr('href'),
				category 		 = jQuery(parent).find('> a span').not('.ui-menu-icon').text(),
				menu 			 = jQuery(parent).find('> ul');
				var categoryLink = jQuery('<a>')
	                .attr('href', categoryUrl)
	                .text($.mage.__('View All ') + category);

	            var categoryParent = jQuery('<li>')
	                .addClass('ui-menu-item all-categories')
	                .html(categoryLink);
	                menu.prepend(categoryParent);
	            if (menu.find('>.all-categories').length === 0) {
	                menu.prepend(categoryParent);
	            }
			});
		}
	});