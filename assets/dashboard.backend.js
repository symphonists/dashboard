jQuery(document).ready(function() {
	
	var dashboard_menu_item = jQuery('#nav a[href$="/extension/dashboard/"]');
	
	dashboard_menu_item.parents('li:last')
		.addClass('dashboard')
		.bind('click', function() {
			window.location.href = dashboard_menu_item.attr('href');
		});
			
});