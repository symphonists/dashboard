/*-----------------------------------------------------------------------------
Language strings
-----------------------------------------------------------------------------*/	 

Symphony.Language.add({
	'Untitled Panel': false,
}); 


/*-----------------------------------------------------------------------------
Dashboard
-----------------------------------------------------------------------------*/

var Dashboard = {
	
	$dashboard: null,
	$h2: null,
	edit_mode: false,
	
	init: function() {
		var self = this;

		this.$dashboard = jQuery('#dashboard');
		this.$h2 = jQuery('h2:first');
		
		// Create New button
		jQuery('a.edit-mode').bind('click', function(e) {
			e.preventDefault();		
			self.edit_mode = !self.edit_mode;
			
			var text = jQuery(this).text();
			var title = jQuery(this).attr('title');
			
			jQuery(this).text(title).attr('title', text);
			
			if (self.edit_mode === true) {
				self.$dashboard.addClass('edit');
				jQuery('.primary, .secondary').sortable('enable');
			} else {
				self.$dashboard.removeClass('edit');
				jQuery('.primary, .secondary').sortable('disable');
			}
			
		});
		
		// Create New button
		jQuery('#select-panel-type li').live('click', function(e) {
			e.preventDefault();		
			self.showEditForm(jQuery(this).attr('class'));
		});
		
		jQuery('a[id^="select-"]').each(function(e) {
			var button = jQuery(this);
			var id = button.attr('id').replace(/select-/,'');
			var html = jQuery('<div class="create button select-button" id="select-'+id+'"><span>'+button.text()+' &darr;</span><ul/></div>');
			
			button.after(html).remove();
			
			var select = jQuery('select[name="'+id+'"]').addClass('hide');
			
			var button = jQuery('#select-' + id);
			var ul = button.find('ul');
			select.find('option').each(function(i, el) {
				var option = jQuery(el);
				ul.append('<li class="'+option.attr('value')+'">'+option.text()+'</li>');
			});
		});

		// Edit panel button
		jQuery('.panel a.panel-edit').live('click', function(e) {
			e.preventDefault();		
			var id = jQuery(this).parent().attr('id').replace(/id-/,'');
			var panel_type = jQuery(this).parent().attr('class').replace(/panel /,'');
			self.showEditForm(panel_type, id);
		});

		// Delete panel button
		jQuery('#save-panel button[name="action[delete]"]').live('click', function(e) {
			e.preventDefault();
			self.savePanel(jQuery('form').serialize(), 'delete');
		});
		
		// Cancel form button
		jQuery('#save-panel input[name="action[cancel]"]').live('click', function(e) {
			e.preventDefault();
			self.hideEditForm(null, true);
		});
		
		// Save panel button
		jQuery('#save-panel input[name="action[submit]"]').live('click', function(e) {
			e.preventDefault();
			self.savePanel(jQuery('form').serialize(), 'submit');
		});
		
		// Save panel button (form submit default)
		jQuery('form').bind('submit', function(e) {
			e.preventDefault();
			self.savePanel(jQuery('form').serialize(), 'submit');
		});
		
		jQuery('.primary, .secondary').sortable({
			items: '.panel',
			connectWith: '.sortable-container',
			placeholder: 'panel-placeholder',
			handle: '> h3',
			revert: 200,
			disabled: true,
			start: function(event, ui) {
				jQuery('.panel-placeholder').height(ui.helper.height() + parseInt(ui.helper.css('padding-top')) + parseInt(ui.helper.css('padding-bottom')) + parseInt(ui.helper.css('border-top-width')) + parseInt(ui.helper.css('border-bottom-width')));
			},
			stop: function() {
				self.saveReordering();
			}
		});

		jQuery('.primary, .secondary').droppable({
			activeClass: 'hover',
			hoverClass: 'active'	
		});
		
		// Panel name
		jQuery('#save-panel input[name="panel[label]"]').live('keyup change', function(e) {
			var name = jQuery(e.target).val();
			var title = jQuery('#save-panel h3 span');
			if(name) {
				title.text(name);
			}
			else {
				title.text(Symphony.Language.get('Untitled Panel'));
			}
		});
		
	},
	
	saveReordering: function() {
		
		var post_data = '';
		var i = 0;
		
		jQuery('.primary, .secondary').each(function(j) {
			var sort_order = 1;
			jQuery('.panel', this).each(function() {
				post_data += 'panel[' + i + '][id]=' + jQuery(this).attr('id').replace(/^id-/,'') + '&';
				post_data += 'panel[' + i + '][placement]=' + ((j == 0) ? 'primary' : 'secondary') + '&';
				post_data += 'panel[' + i + '][sort_order]=' + sort_order++ + '&';
				i++;
			});
		});
		
		jQuery.ajax({
			type: 'POST',
			url: Symphony.Context.get('root') + '/symphony/extension/dashboard/save_order/',
			data: post_data,
		});
		
	},
	
	hideEditForm: function(callback, enable_dashboard) {
		if (enable_dashboard === true) this.$dashboard.fadeTo('fast', 1);
		jQuery('#save-panel').slideUp(function() {
			jQuery(this).remove();
			if (typeof callback == 'function') callback();
		});
	},
	
	showEditForm: function(panel_type, id) {
		var self = this;
		jQuery.ajax({
			type: 'GET',
			url: Symphony.Context.get('root') + '/symphony/extension/dashboard/panel_config/?type=' + panel_type + ((id != null) ? ('&id=' + id) : ''),
			success: function(data) {
				var form = jQuery('#save-panel');
				// if form exists in the DOM it needs to be removed first
				// before the new form is revealed
				if (form.length) {
					self.hideEditForm(function() {
						self.revealEditForm(data);
					}, false);
				} else {
					self.revealEditForm(data);
				}
				
				// Update title
				jQuery('#save-panel input[name="panel[label]"]').change();
			}
		});
	},
	
	revealEditForm: function(html) {
		// fade down dashboard panels to give edit form more priority
		this.$dashboard.fadeTo('fast', 0.25);
		// append form to page (hidden with CSS)
		this.$h2.after(html);
		jQuery('#save-panel').slideDown();
	},
	
	savePanel: function(post_data, action) {
		var self = this;
		
		post_data += '&action[' + action + ']=true';
		
		jQuery.ajax({
			type: 'POST',
			url: Symphony.Context.get('root') + '/symphony/extension/dashboard/panel_config/',
			data: post_data,
			success: function(data) {
				// Must be an error, show the form again:
				if (jQuery('response', data).length == 0) {
					if (jQuery('#save-panel').length) {
						self.hideEditForm(function() {
							self.revealEditForm(data);
						}, false);
					}

					else {
						self.revealEditForm(data);
					}

					return;
				}

				var id = jQuery('response', data).attr('id');
				var placement = jQuery('response', data).attr('placement');
				
				var html = jQuery('response', data).text();
				var panel = jQuery('#id-' + id);
				
				switch (action) {
					
					case 'delete':
						self.hideEditForm(function() {
							panel.slideUp('slow', function() {
								jQuery(this).remove();
							});
						}, true);
					break;
					
					case 'submit':
						// insert new panel
						if (panel.length == 0) {
							self.hideEditForm(function() {
								jQuery('.' + placement).append(html);
								jQuery('.new-panel').slideDown('fast', function() {
									jQuery(this).removeClass('new-panel');
								})
							}, true);
						}
						// update
						else {
							self.hideEditForm(function() {
								var column = panel.parent();
								var column_name = ((panel.parent().hasClass('primary')) ? 'primary' : 'secondary');
								panel.fadeOut('fast', function() {
									if (placement == column_name) {
										jQuery(this).after(html).remove();
									} else {
										jQuery(this).remove();
										jQuery('.' + placement).append(html);
									}
									jQuery('#id-' + id).hide().removeClass('new-panel').fadeIn('fast');
								});
							}, true);
						}
					break;
				}
			}
		});
	}
	
};

jQuery(document).ready(function() {
	Dashboard.init();
});