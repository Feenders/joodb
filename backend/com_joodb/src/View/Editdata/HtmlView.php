<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

namespace Feenders\Component\Joodb\Administrator\View\Editdata;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Utilities\ArrayHelper;
use function defined;


// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
{
	var $id = 0;
	var $jb = null;
	var $item = null;

	function display($tpl = null)
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$input = Factory::getApplication()->input;

		$input->set( 'hidemainmenu', 1 );

		// load the jooDb object with table field infos
		$this->jb = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
		$this->jb->load( $input->getInt( 'joodbid') );
		$db	= $this->jb->getTableDBO();
		$this->jb->fields = $db->getTableColumns($this->jb->table,false);

		// get the item to edit
		if ($cid = $input->get( 'cid', null, 'array')) {
			ArrayHelper::toInteger( $cid );
			$this->id = $cid[0];
			$db->setQuery("SELECT * FROM `".$this->jb->table."` WHERE `".$this->jb->fid."`=".$db->quote($this->id),0,1);
			$this->item = $db->loadObject();
		} else {
            $this->item = new \stdClass();
        }
		$text = ( $this->item ? Text::_("JDB_EDIT") : Text::_("JDB_NEW") );
		ToolBarHelper::title(   $this->jb->name.': <small><small>['.$text.']</small></small>','database' );
		ToolBarHelper::apply('applydata');
		ToolBarHelper::save('savedata');
		ToolBarHelper::save2copy('savecopydata');
		ToolBarHelper::cancel('editdata.cancel');

		parent::display($tpl);
	}

}