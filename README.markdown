# Dashboard

## Purpose
To provide a Dashboard summary screen for users. Dashboard "panels" can contain any information. This extension provides the framework for building a Dashboard, and provides four basic panel types. Other extensions can provide their own panel types.

## Installation
 
1. Upload the 'dashboard' folder in this archive to your Symphony 'extensions' folder
2. Enable it by selecting "Dashboard" in the list, choose Enable from the with-selected menu, then click Apply
3. Navigate to the Dashboard from the "Dashboard" link in the primary navigation

## Setting the Dashboard as the user's default

Once installed "Dashboard" will appear in the "Default area" dropdown when you create a new author. If you choose this, the author will be shown the dashboard when they log in.

## Core panel types

There are five core panel types:

* **Datasource to Table** takes a Symphony Data Source and attempts to render it as an HTML table. This works best with basic fields such as Text Input, Checkboxes and Dates. The first column will link to the entry itself.
* **HTML Block** allows you to specify the URL of a page that outputs a chunk of HTML (a `<div />` perhaps) to include in the panel.
* **Markdown Text Block** allows you to add Markdown-formatted text to include in the panel.
* **RSS Feed Reader** parses an RSS feed and renders the summary. Useful for latest news or updates.
* **Symphony Overview** renders basic statistics about your installation such as version number and total number of entries.

## Extensions that provide panels

The delegates that Dashboard provides means that other extensions can supply their own dashboard panels. These include:

* [Tracker](http://symphonyextensions.com/extensions/tracker/) panel shows summary of all author and developer activity within Symphony
* [Search Index](http://symphonyextensions.com/extensions/search_index/) panel shows a list of recent searches
* [Sections Panel](http://symphonyextensions.com/extensions/sections_panel/) shows latest entries from a section (without creating a data source)
* [Health Check](http://symphonyextensions.com/extensions/health_check/) shows test results against your server to determine the best permissions for your environment.
* [Google Analytics](http://symphonyextensions.com/extensions/google_analytics_dashboard/) allows you to create panels that displays Google Analytics Charts.

## Creating your own panel types

To provide panels your extension needs to implement (subscribe to) two delegates:

	public function getSubscribedDelegates() {
		return array(
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'DashboardPanelRender',
				'callback'	=> 'render_panel'
			),
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'DashboardPanelTypes',
				'callback'	=> 'dashboard_panel_types'
			),
		);
	}

There are two additional delegates to provide UI for panel settings, and its validation:

	array(
		'page'		=> '/backend/',
		'delegate'	=> 'DashboardPanelOptions',
		'callback'	=> 'dashboard_panel_options'
	),
	array(
		'page'		=> '/backend/',
		'delegate'	=> 'DashboardPanelValidate',
		'callback'	=> 'dashboard_panel_validate'
	),

These are optional unless your panel configuration requires user input.

### DashboardPanelTypes

The callback function should return the handle and name of your panel(s) by adding a new key to the `types` array:

	public function dashboard_panel_types($context) {
		$context['types']['my_dashboard_panel'] = 'My Amazing Dashboard Panel';
	}

This will define a panel of type `my_dashboard_panel`. Make this name as unique as possible so it doesn't conflict with others.

### DashboardPanelOptions

Each panel has a configuration screen. There are default options for all panels ("Label" and "Position"), but you can add additional elements to the configuration form using the `DashboardPanelOptions` delegate:

	public function dashboard_panel_options($context) {
		// make sure it's your own panel type, as this delegate fires for all panel types!
		if ($context['type'] != 'my_dashboard_panel') return;
		
		$config = $context['existing_config'];
	
		$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
		$fieldset->appendChild(new XMLElement('legend', 'My Panel Options'));
	
		$label = Widget::Label('Option 1', Widget::Input('config[option-1]', $config['option-1']));
		$fieldset->appendChild($label);

		$context['form'] = $fieldset;
	
	}

The above code creates a fieldset which will be appended to the panel configuration form. The fieldset contains a single textfield with the label "Option 1". The `$config` array contains existing saved options, so you can pre-populate your form fields when editing an existing panel.

Upon saving, all form fields named in the `config[...]` array will be saved with this panel instance, and provided to the panel as an array when it renders.

### DashboardPanelRender

Subscribe to the `DashboardPanelRender` delegate to render your panel on the dashboard.

	public function render_panel($context) {
		if ($context['type'] != 'my_dashboard_panel') return;
		
		$config = $context['config'];
		$context['panel']->appendChild(new XMLElement('div', 'The value of Option 1 is: ' . $config['option-1']));
	}

First check that you should output your own panel. `$context['panel']` contains an `XMLElement` that is an empty panel container to which you can append children. The saved configuration for the panel is presented in the `$context['config']` array.

* * *

## Known issues
* adding Markdown panels using different versions of the Markdown formatter will cause an error. Be sure to always use the same Markdown formatter for all panels