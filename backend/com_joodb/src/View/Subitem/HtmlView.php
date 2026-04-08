<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

namespace Feenders\Component\Joodb\Administrator\View\Subitem;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;
use function defined;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
{
    var $bart = null;
	var $item = null;
	var $config = null;	
	var $tables = array();
	var $fields = array();	
	var $joobase = null;	
	var $version = null;

	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$this->config = ComponentHelper::getParams('com_joodb');
		$this->version = new Version();
		$document = Factory::getApplication()->getDocument();

		$this->bar =  $document->getToolbar('toolbar');
		$id = $app->input->getCmd('id');
		$text = ( $id ? Text::_("JDB_EDIT") : Text::_("JDB_NEW") );
	 	ToolBarHelper::title(Text::_("JDB_LINKED_TABLES").': <small><small>['.$text.']</small></small>', 'joodb.png' );
	 	ToolBarHelper::save('save');
	 	ToolBarHelper::cancel('close');

		$this->item = new \Feenders\Component\Joodb\Administrator\Table\SubitemTable($db);
		$this->item->load( $id );

		$this->joobase = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
		$this->joobase->load($app->input->getInt('jbid'));
		$tdb = $this->joobase->getTableDBO();
		$this->tables = $tdb->getTableList();		

		$tdb->setQuery("SHOW COLUMNS FROM `".$this->joobase->table."`");
		$this->fields = $tdb->loadObjectList();

		parent::display($tpl);

	}

}