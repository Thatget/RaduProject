require([
	'jquery'
	], function($){
		$('.header.links .icon.menu').click(function(){
			$('.header.content .action.nav-toggle').trigger("click");
			$('.page-wrapper .page-header .header.links >.minicart-wrapper .mage-dropdown-dialog').hide();
			$('.page-wrapper .page-header .header.links >.minicart-wrapper').removeClass('active');
			if($('.header.links .icon.menu').hasClass('active')) {
				$('.header.links .icon.menu').removeClass('active');
				$('html').removeClass('nav-open');
			}
			else {
				$('html').addClass('nav-open');
				$('header.page-header').removeClass('search-active');
				$('.header.links .icon.search').removeClass('active');
				$('.header.links .icon.menu').addClass('active');
				$('.main-account-wrapper').removeClass('active');
				$('.sections.nav-sections .section-item-title[aria-controls="store.menu"]').trigger("click");

			}
		});

		$('.main-account-wrapper').click(function(){
			$('.page-wrapper .page-header .header.links >.minicart-wrapper .mage-dropdown-dialog').hide();
			$('.page-wrapper .page-header .header.links >.minicart-wrapper').removeClass('active');
			if($('.main-account-wrapper').hasClass('active')) {
				$('.main-account-wrapper').removeClass('active');
				$('html').removeClass('nav-open');
			}
			else {
				$('header.page-header').removeClass('search-active');
				$('.header.links .icon.search').removeClass('active');
				$('.main-account-wrapper').addClass('active');
				$('.header.links .icon.menu').removeClass('active');
				$('html').addClass('nav-open');
				$('.sections.nav-sections .section-item-title[aria-controls="store.links"]').trigger("click");
			}
		});

		$('.header.links .icon.search').click(function(){
			$('.page-wrapper .page-header .header.links >.minicart-wrapper .mage-dropdown-dialog').hide();
			$('.page-wrapper .page-header .header.links >.minicart-wrapper').removeClass('active');
			$('.block-search .minisearch label[data-role="minisearch-label"]').trigger("click");
			if($('header.page-header').hasClass('search-active')) {
				$('header.page-header').removeClass('search-active');
				$('.header.links .icon.search').removeClass('active');
			}
			else {
				$('header.page-header').addClass('search-active');
				$('.header.links .icon.search').addClass('active');
				$('.main-account-wrapper').removeClass('active');
				$('.header.links .icon.menu').removeClass('active');
				$('html').removeClass('nav-open');
			}
		});
		
		$('.page-wrapper .page-header .header.links >.minicart-wrapper >a').click(function(e){
			$.each($('.minicart-wrapper .ui-dialog .action.close'), function(key, item){
				$(item).removeAttr('id', 'none');
			});
			$('.page-wrapper .page-header .header.links >.minicart-wrapper .mage-dropdown-dialog').toggle();
			console.log($('.page-wrapper .page-header .header.links >.minicart-wrapper'));
			if(!$('.page-wrapper .page-header .header.links >.minicart-wrapper').hasClass('active'))
				$('.page-wrapper .page-header .header.links >.minicart-wrapper').addClass('active');
			else $('.page-wrapper .page-header .header.links >.minicart-wrapper').removeClass('active');
			$('header.page-header').removeClass('search-active');
			$('.header.links .icon.search').removeClass('active');
			$('.header.links .icon.menu').removeClass('active');
			$('html').removeClass('nav-open');
			$('.main-account-wrapper').removeClass('active');
			$('.main-account-wrapper >.account-links').css('display', 'none');
			$('.minicart-wrapper >.ui-dialog .action.close').click(function(){
				$('.page-wrapper .page-header .header.links >.minicart-wrapper').removeClass('active');
				$('.page-wrapper .page-header .header.links >.minicart-wrapper .mage-dropdown-dialog').css('display','none');
			});
			e.preventDefault();
			return false;
		});


		$('.panel.header >.header.links').click(function(){
			if($( window ).width() < 771) {
				var elementHeight = $('.sections.nav-sections').height();
				$("#maincontent").css('padding-top', elementHeight+'px');
			}
		})
		if($(window).width() < 771 && $(window).width() >=768) {
			$('.level0.ui-menu-item.parent >a').click(function(e){
				if($(e.currentTarget).parent().find(">ul.submenu").length)
				{
					if($(e.currentTarget).parent().hasClass('ui-state-active'))
					{
						$(e.currentTarget).parent().removeClass('ui-state-active');
						$(e.currentTarget).parent().find(">ul.submenu").css('display', 'none');
					}
					else {
						$(e.currentTarget).parent().addClass('ui-state-active');
						$(e.currentTarget).parent().find(">ul.submenu").css('display', 'block');
					}
				}
				return false;
			});
		}
		if($(window).width() < 771)
		{
			$('nav.navigation >ul.ui-menu li.ui-menu-item.level1.parent a').click(function(e){
				if($(e.currentTarget).parent().find(">ul.submenu").length)
				{
					if($(e.currentTarget).parent().hasClass('ui-state-active'))
					{
						$(e.currentTarget).parent().removeClass('ui-state-active');
					}
					else $(e.currentTarget).parent().addClass('ui-state-active');
					return false;
				}
			});
			$(".ui-menu-item.all-category >a").each(function(key, element){
				$(element).text("View " + $(element).text());
			});
			$(".level2.ui-menu-item >a.ui-corner-all >span").each(function(key, element){
				$(element).text("View " + $(element).text());

			});

		}
		
	});