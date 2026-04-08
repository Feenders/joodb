<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

namespace Feenders\Component\Joodb\Administrator\View\Joodbentry;

use Exception;
use Feenders\Component\Joodb\Administrator\Helper\JoodbAdminHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Utilities\ArrayHelper;
use RuntimeException;
use function defined;


// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
{
	var $bar = null;
	var $version = null;
	var $fields = array();
	var $tables = array();
	var $config = array();	

	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$db = $this->getDbo();
		$this->config = ComponentHelper::getParams('com_joodb');
		
		$this->version = new Version();
		$this->bar = Factory::getApplication()->getDocument()->getToolbar('toolbar');

		$layout	= $app->input->get('layout');
		if ($layout=="step1") {
	 		ToolBarHelper::title(Text::_("JDB_STEP1_CHOOSE_TABLE"), 'box-add' );
			$this->bar->appendButton('Standard', 'arrow-right', Text::_("JDB_CONTINUE"), 'addnew',false);
			$this->bar->appendButton('Standard', 'power-cord extension', Text::_("JDB_Use_External_Database") , 'extern',false);
			$this->tables = $db->getTableList();
		} else if ($layout=="extern") {
			ToolBarHelper::title(Text::_("JDB_EXTERNAL_DATABASE"), 'box-add' );
            $this->bar->appendButton('Standard', 'cancel', Text::_("JDB_BACK"), 'cancel',false);
			$this->bar->appendButton('Standard', 'arrow-right', Text::_("JDB_CONTINUE"), 'addnew',false);
		} else if ($layout=="step2") {
			ToolBarHelper::title(Text::_("JDB_STEP2_DEFINE_FIELDS"), 'box-add' );
			$this->bar->appendButton('Standard', 'arrow-right', Text::_("JDB_CONTINUE"), 'addnew',false);
			$this->dbtable = $app->input->getString('dbtable');
			$this->dbname = $app->input->getString('dbname');
            $this->fields = $db->getTableColumns($this->dbtable,false);
		} else if ($layout=="step3") {
			// Add new entry
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$item = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
			if (!$item->save( $_POST )) {
				throw new RuntimeException($item->getError(),500);
			}
	 		ToolBarHelper::title(Text::_("JDB_STEP3_NO_STEP"), 'box-add' );
			$this->bar->appendButton('Standard', 'cancel', Text::_("JDB_CLOSE"), 'close',false);
		} else {
			$cid = $app->input->get( 'cid', array(),'array' );
			ArrayHelper::toInteger( $cid );
			$id = $cid[0];
			ToolBarHelper::apply();
			ToolBarHelper::save();
			ToolBarHelper::cancel();
			$bar = Factory::getApplication()->getDocument()->getToolbar('toolbar');
			$bar->appendButton('Help', 'http://joodb.feenders.de/support.html', false, 'http://joodb.feenders.de/support.html', null);

			$item = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
			if (!$item->load( $id )) {
				$app->enqueueMessage( Text::_($item->getError()), 'error' );
			} else {
				$tdb = $item->getTableDBO();
				$tdb->setQuery("SHOW COLUMNS FROM `".$item->table."`");
				$this->fields = $tdb->loadObjectList();
				$this->tables = $tdb->getTableList();
			}
			$item->subitems = $item->getSubitems();

			$params = Form::getInstance('config_items',JPATH_COMPONENT_ADMINISTRATOR.'/config_items.xml',array('control' => 'params', 'load_data' => true),  false,'/config');
			$params->bind($item->getParameters());
			$this->params = $params;
			$this->item = $item;

			ToolBarHelper::title( (!empty($item->name) ? $item->name : Text::_( "JooDatabase" )).': <small><small>['.Text::_("JDB_EDIT").']</small></small>','database' );
			$app->input->set( 'hidemainmenu', 1 );
		}

		parent::display($tpl);
	}

	/**
	 * Get external DB if external server ...
	 * @return DatabaseFactory
	 */
	function getDbo() {
		$app = Factory::getApplication();
		$input = $app->input->post;
		if (!empty($_POST['server'])) {
			$options = array ('host' => $input->getString('server'), 'user' => $input->getString('user'), 'password' => $input->getString('pass'), 'database' => $input->getString('database'), 'prefix' => '');
			try {
				$dbf = new DatabaseFactory();
				$db = $dbf->getDriver('mysqli',$options);
			} catch (Exception $e) {
				$this->setError('Database Error: ' .$e->getMessage());
			}
			return $db;
		}
		return Factory::getContainer()->get(DatabaseInterface::class);
	}

}