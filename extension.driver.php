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
				'delegate'	=> 'DashboardPanelRender',
				'callback'	=> 'render_panel'
			),
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'DashboardPanelOptions',
				'callback'	=> 'dashboard_panel_options'
			),
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'DashboardPanelTypes',
				'callback'	=> 'dashboard_panel_types'
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
		return Symphony::Database()->fetch('SELECT * FROM sym_dashboard_panels ORDER BY sort_order ASC');
	}
	
	public static function getPanel($panel_id) {
		return Symphony::Database()->fetchRow(0, "SELECT * FROM sym_dashboard_panels WHERE id='{$panel_id}'");
	}
	
	public static function deletePanel($panel) {
		return Symphony::Database()->query("DELETE FROM sym_dashboard_panels WHERE id='{$panel['id']}'");
	}
	
	public static function updatePanelOrder($id, $placement, $sort_order) {
		$sql = sprintf(
			"UPDATE sym_dashboard_panels SET
			placement = '%s',
			sort_order = '%d'
			WHERE id = '%d'",
			Symphony::Database()->cleanValue($placement),
			Symphony::Database()->cleanValue($sort_order),
			(int)$id
		);
		return Symphony::Database()->query($sql);
	}
	
	public static function savePanel($panel=NULL, $config=NULL) {
		if ($panel['id'] == '') {
			
			return Symphony::Database()->query(sprintf(
				"INSERT INTO sym_dashboard_panels 
				(label, type, config, placement, sort_order)
				VALUES('%s','%s','%s','%s','%d')",
				Symphony::Database()->cleanValue($panel['label']),
				Symphony::Database()->cleanValue($panel['type']),
				serialize($config),
				Symphony::Database()->cleanValue($panel['placement']),
				0
			));
			
		} else {
			
			return Symphony::Database()->query(sprintf(
				"UPDATE sym_dashboard_panels SET
				label = '%s',
				config = '%s',
				placement = '%s'
				WHERE id = '%d'",
				Symphony::Database()->cleanValue($panel['label']),
				serialize($config),
				Symphony::Database()->cleanValue($panel['placement']),
				(int)$panel['id']
			));
			
		}

	}
	
	public static function buildPanelOptions($type, $panel_id) {
		
		$panel_config = self::getPanel($panel_id);
		
		$form = null;
		Administration::instance()->ExtensionManager->notifyMembers('DashboardPanelOptions', '/backend/', array(
			'type'				=> $type,
			'form'				=> &$form,
			'existing_config'	=> unserialize($panel_config['config'])
		));

		return $form;
		
	}
	
	public function dashboard_panel_types($context) {
		$context['types']['datasource_to_table'] = 'Datasource to Table';
		$context['types']['rss_reader'] = 'RSS Feed Reader';
	}

	public function dashboard_panel_options($context) {
		
		$config = $context['existing_config'];
		
		switch($context['type']) {
			
			case 'datasource_to_table':
				
				require_once(TOOLKIT . '/class.datasourcemanager.php');
				$dsm = new DatasourceManager(Administration::instance());
				$datasources = array();
				foreach($dsm->listAll() as $ds) $datasources[] = array($ds['handle'], ($config['datasource'] == $ds['handle']), $ds['name']);

				$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
				$fieldset->appendChild(new XMLElement('legend', 'Data Source to Table'));
				$label = Widget::Label('Data Source', Widget::Select('config[datasource]', $datasources));
				$fieldset->appendChild($label);

				$context['form'] = $fieldset;
				
			break;
			
			case 'rss_reader':
			
				$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
				$fieldset->appendChild(new XMLElement('legend', 'RSS Reader'));
				$label = Widget::Label('Feed URL', Widget::Input('config[url]', $config['url']));
				$fieldset->appendChild($label);

				$context['form'] = $fieldset;

			break;
		}

	}
		
	public function render_panel($context) {
		
		switch($context['type']) {
			
			case 'datasource_to_table':

				require_once(TOOLKIT . '/class.datasourcemanager.php');
				$dsm = new DatasourceManager(Administration::instance());

				$ds = $dsm->create($context['config']['datasource'], NULL, false);
				$xml = $ds->grab()->generate();

				require_once(TOOLKIT . '/class.xsltprocess.php');
				$proc = new XsltProcess();
				$data = $proc->process($xml, file_get_contents(EXTENSIONS . '/dashboard/lib/table.xsl'));

				$context['panel']->appendChild(new XMLElement('div', $data));
			
			break;
			
			case 'rss_reader':
			
				$context['panel']->appendChild(new XMLElement('div', 'Nothing to see yet, but this will parse an RSS feed.'));
			
			break;
			
		}
		
	}
		
}