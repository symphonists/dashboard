jQuery(document).ready(function() {
	
	var dashboard_menu_item = jQuery('#nav a[href$="/extension/dashboard/index/"]');
	var dashboard_menu_group = dashboard_menu_item.parents('li:last');
	
	// kill the subnav
	dashboard_menu_group.find('ul').remove();
	
	dashboard_menu_group
		.css('cursor', 'pointer')
		.remove()
		.prependTo('#nav ul.content')
		.bind('click', function() {
			window.location.href = dashboard_menu_item.attr('href');
		})
			
});