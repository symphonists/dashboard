<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/dashboard/extension.driver.php');

Class contentExtensionDashboardPanel_Config extends AdministrationPage {
	
	public function __construct(&$parent) {
		parent::__construct($parent);
	}
	
	public function __actionIndex() {
		
		$panel = $_POST['panel'];
		$config = $_POST['config'];
		
		if(isset($_POST['action']['submit'])) {
			Extension_Dashboard::savePanel($panel, $config);
		}
		elseif(isset($_POST['action']['delete'])) {
			Extension_Dashboard::deletePanel($panel);
		}
		
		echo '<html><body>';
		echo '<script type="text/javascript">window.parent.history.go(0);window.parent.jQuery.fancybox.close();</script>';
		echo '</body></html>';
		die;
		
	}
	
	public function __viewIndex() {
		
		$this->setPageType('form');
		$this->setTitle(__('Symphony') . ' &ndash; ' . __('Dashboard'));
		
		$this->addScriptToHead(URL . '/extensions/dashboard/assets/fancybox/jquery.fancybox-1.3.1.js', 948131);
		$this->addStylesheetToHead(URL . '/extensions/dashboard/assets/fancybox/jquery.fancybox-1.3.1.css', 'screen', 948131);
		
		$this->addStylesheetToHead(URL . '/extensions/dashboard/assets/dashboard_iframe.css', 'screen', 948131);
		
		$heading = new XMLElement('h2', __('Panel Configuration'));
		$this->Form->appendChild($heading);
		
		$id = $_GET['id'];
		$type = $_GET['type'];
		
		$panel_config = Extension_Dashboard::getPanel($id);
		$config_options = Extension_Dashboard::buildPanelOptions($type, $id);
		
		if (!$config_options) die('No config found.');
		
		$primary = new XMLElement('div', NULL, array('class' => 'panel-config'));
		
		$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
		$legend = new XMLElement('legend', 'General settings');
		$fieldset->appendChild($legend);
		$fieldset->appendChild(Widget::Label('Panel Name',
			Widget::Input('panel[label]', $panel_config['label'])
		));
		$fieldset->appendChild(Widget::Label('Placement', 
			Widget::Select('panel[placement]', array(
				array('primary', ($panel_config['placement'] == 'primary'), 'Main Column'),
				array('secondary', ($panel_config['placement'] == 'secondary'), 'Sidebar')
			))
		));
		$primary->appendChild($fieldset);
		
		$primary->appendChild($config_options);
		
		$actions = new XMLElement('div', NULL, array('class' => 'actions'));
		$actions->appendChild(Widget::Input('action[submit]', 'Save Panel', 'submit'));
		if ($id) {
			$actions->appendChild(new XMLElement('button', 'Delete Panel', array(
				'class' => 'confirm delete',
				'title' => 'Delete this panel',
				'name' => 'action[delete]'
			)));
		}
		$primary->appendChild($actions);
		
		$primary->appendChild(Widget::Input('panel[id]', $id, 'hidden'));
		$primary->appendChild(Widget::Input('panel[type]', $type, 'hidden'));
		
		$this->Form->appendChild($primary);
		
	}
	
}