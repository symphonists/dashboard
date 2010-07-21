<?php

Class Extension_Dashboard extends Extension{
	
	private $root_url = '/symphony/extension/dashboard/index/';
	
	public function about() {
		return array('name' => 'Dashboard',
					 'version' => '0.1',
					 'release-date' => '2010-07-16',
					 'author' => array('name' => 'Nick Dunn',
									   'website' => 'http://nick-dunn.co.uk',
									   'email' => ''),
						'description'   => 'Provide a dashboard for your users'
			 		);
	}

	public function install() {
		// create table
		
		/*
		CREATE TABLE `sym_dashboard_panels` (
		  `id` int(11) NOT NULL auto_increment,
		  `type` varchar(255) default NULL,
		  `config` text,
		  `order` int(11) default NULL,
		  `placement` varchar(255) default NULL,
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM;
		*/
		
		/*
		INSERT INTO `sym_dashboard_panels` (`id`,`type`,`config`,`order`,`placement`)
		VALUES
			(1,'html_panel',NULL,1,'primary'),
			(2,'html_panel',NULL,4,'secondary'),
			(3,'table_data_source','a:1:{s:11:\"data-source\";s:7:\"articles\";}',5,'primary');
		*/
	}

       public function uninstall() {
		// drop table
	}

	
	public function getSubscribedDelegates() {
		return array(
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'ExtensionsAddToNavigation',
				'callback'	=> 'add_navigation'
			),
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'AdminPagePreGenerate',
				'callback'	=> 'page_pre_generate'
			),
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'RenderDashboardPanel',
				'callback'	=> 'table_data_source'
			),
		);
	}
	
	
	public function add_navigation($context) {
		$context['navigation'][-1] = array(
			'name'		=> __('Dashboard'),
			'index'		=> '1',
			'children'	=> array(
				array(
					'link'		=> '/' . preg_replace('/\/symphony\//', '', $this->root_url),
					'name'		=> __('Dashboard'),
					'visible'	=> 'yes'
				),
			),
		);
	}
	
	public function page_pre_generate($context) {
		// when arriving after logging-in, redirect to Dashboard
		if (preg_match('/\/symphony\/$/', $_SERVER['HTTP_REFERER'])) redirect($this->root_url);
		
		
	}
	
	public static function getPanels() {
		return Symphony::Database()->fetch('SELECT * FROM sym_dashboard_panels');
	}
	
	public function table_data_source($context) {
		if ($context['type'] != 'table_data_source') return;
		
		require_once(TOOLKIT . '/class.datasourcemanager.php');
		$dsm = new DatasourceManager(Administration::instance());
		
		$ds = $dsm->create($context['config']['data-source'], NULL, false);
		$xml = $ds->grab()->generate();
		
		require_once(TOOLKIT . '/class.xsltprocess.php');
		$proc = new XsltProcess();
		$data = $proc->process($xml, file_get_contents(EXTENSIONS . '/dashboard/lib/table.xsl'));
		
		$context['panel']->appendChild(new XMLElement('div', $data));
		
	}
		
}