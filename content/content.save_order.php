<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/dashboard/extension.driver.php');

Class contentExtensionDashboardSave_Order extends AdministrationPage {
	
	public function __construct(&$parent) {
		parent::__construct($parent);
	}
	
	public function __viewIndex() {
		
		$panels = $_POST['panel'];
		foreach($panels as $panel) {
			//var_dump($panel);die;
			Extension_Dashboard::updatePanelOrder($panel['id'], $panel['placement'], $panel['sort_order']);
		}
		
	}
	
}