<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/dashboard/extension.driver.php');

Class contentExtensionDashboardIndex extends AdministrationPage {
	
	public function __construct(&$parent) {
		parent::__construct($parent);
	}
	
	public function __viewIndex() {
		
		$this->setPageType('form');
		$this->setTitle(__('Symphony') . ' &ndash; ' . __('Dashboard'));
		
		$this->addScriptToHead(URL . '/extensions/dashboard/assets/jquery-ui-1.8.2.custom.min.js', 948132);
		$this->addScriptToHead(URL . '/extensions/dashboard/assets/fancybox/jquery.fancybox-1.3.1.js', 948133);
		$this->addStylesheetToHead(URL . '/extensions/dashboard/assets/fancybox/jquery.fancybox-1.3.1.css', 'screen', 948134);
		
		$this->addStylesheetToHead(URL . '/extensions/dashboard/assets/dashboard.css', 'screen', 948131);
		$this->addScriptToHead(URL . '/extensions/dashboard/assets/dashboard.js', 948132);
		
		$heading = new XMLElement('h2', __('Dashboard'));
		$create_new = new XMLElement('a', __('Create Panel'), array(
			'class'	=> 'create button',
			'href'	=> '#'
		));
	
		$panel_types = array();
		Administration::instance()->ExtensionManager->notifyMembers('DashboardPanelTypes', '/backend/', array(
			'types' => &$panel_types
		));
		
		$panel_types_options = array();
		foreach($panel_types as $handle => $name) {
			$panel_types_options[] = array($handle, false, $name);
		}
	
		$heading->appendChild($create_new);
		$heading->appendChild(Widget::Select('panel-type', $panel_types_options));
		
		$this->Form->appendChild($heading);
		
		$container = new XMLElement('div', NULL, array('id' => 'dashboard'));
		
		$primary = new XMLElement('div', NULL, array('class' => 'primary sortable-container'));
		$secondary = new XMLElement('div', NULL, array('class' => 'secondary sortable-container'));
		
		$panels = Extension_Dashboard::getPanels();
		
		foreach($panels as $p) {
			
			$html = Extension_Dashboard::buildPanelHTML($p);
			
			switch($p['placement']) {
				case 'primary': $primary->appendChild($html); break;
				case 'secondary': $secondary->appendChild($html); break;
			}
			
		}
		
		$container->appendChild($primary);
		$container->appendChild($secondary);		
		$this->Form->appendChild($container);
		
	}
	
}