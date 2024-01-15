<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

// no direct access
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

use Joomla\Utilities\ArrayHelper;

class JoodbViewJoodbentry extends JViewLegacy
{
	var $bar = null;
	var $version = null;
	var $fields = array();
	var $tables = array();
	var $config = array();	

	function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$this->config = JComponentHelper::getParams('com_joodb');
		
		$this->version = new JVersion();
		$this->bar = JToolBar::getInstance('toolbar');

		$layout	= $app->input->get('layout');
		if ($layout=="step1") {
	 		JToolBarHelper::title(JText::_( "Step1 choose Table" ), 'box-add' );
			$this->bar->appendButton('Standard', 'arrow-right', JText::_( "Continue" ), 'addnew',false);
			$this->bar->appendButton('Standard', 'power-cord extension', JText :: _('Use External Database') , 'extern',false);
			$db = $this->getDbo();
			$this->tables = $db->getTableList();
		} else if ($layout=="extern") {
	 		JToolBarHelper::title(JText::_( "External Database" ), 'box-add' );
            $this->bar->appendButton('Standard', 'cancel', JText::_( "Back" ), 'cancel',false);
			$this->bar->appendButton('Standard', 'arrow-right', JText::_( "Continue" ), 'addnew',false);
		} else if ($layout=="step2") {
	 		JToolBarHelper::title(JText::_( "Step2 define Fields" ), 'box-add' );
			$this->bar->appendButton('Standard', 'arrow-right', JText::_( "Continue" ), 'addnew',false);
			$this->dbtable = $app->input->getString('dbtable');
			$this->dbname = $app->input->getString('dbname');
			$db = $this->getDbo();
            $this->fields = $db->getTableColumns($this->dbtable,false);
		} else if ($layout=="step3") {
			// Add new entry
			$item = JTable::getInstance('joodb', 'Table');
			if (!$item->save( $_POST )) {
				throw new RuntimeException($item->getError(),500);
			}
	 		JToolBarHelper::title(JText::_( "Step3 no step" ), 'box-add' );
			$this->bar->appendButton('Standard', 'cancel', JText::_( "close" ), 'close',false);
		} else {
			$cid = $app->input->get( 'cid', array(),'array' );
			ArrayHelper::toInteger( $cid );
			$id = $cid[0];
			JToolBarHelper::apply();
			JToolBarHelper::save();
			JToolBarHelper::cancel();
			$bar = JToolBar::getInstance('toolbar');
			$bar->appendButton('Help', 'http://joodb.feenders.de/support.html', false, 'http://joodb.feenders.de/support.html', null);

			$item = JTable::getInstance( 'joodb', 'Table' );
			if (!$item->load( $id )) {
				$app->enqueueMessage( JText::_($item->getError()), 'error' );
			} else {
				$tdb = $item->getTableDBO();
				$tdb->setQuery("SHOW COLUMNS FROM `".$item->table."`");
				$this->fields = $tdb->loadObjectList();
				$this->tables = $tdb->getTableList();
			}
			$item->subitems = $item->getSubitems();

			$params = JForm::getInstance('config_items',JPATH_COMPONENT_ADMINISTRATOR.'/config_items.xml',array('control' => 'params', 'load_data' => true),  false,'/config');
			$params->bind($item->getParameters());
			$this->params = $params;
			$this->item = $item;

			JToolBarHelper::title( (!empty($item->name) ? $item->name : JText::_( "JooDatabase" )).': <small><small>['.JText::_( 'Edit' ).']</small></small>','database' );
			$app->input->set( 'hidemainmenu', 1 );
		}

		parent::display($tpl);
	}

	/**
	 * Get external DB if external server ...
	 * @return JDatabase
	 */
	function getDbo() {
		$input = JFactory::getApplication()->input->post;
		if (!empty($_POST['server'])) {
			$options = array ('host' => $input->getString('server'), 'user' => $input->getString('user'), 'password' => $input->getString('pass'), 'database' => $input->getString('database'), 'prefix' => '');
			try {
				$db = JDatabaseDriver::getInstance($options);
			} catch (\Exception $e) {
				$this->setError('Database Error: ' .$e->getMessage());
			}
			return $db;
		}
		return JFactory::getDbo();
	}

}