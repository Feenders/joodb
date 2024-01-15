<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

// no direct access
defined('_JEXEC') or die();
;
use Joomla\Utilities\ArrayHelper;

// edit or add a custom entry from a table
class JoodbViewEditdata extends JViewLegacy
{
	var $id = 0;
	var $jb = null;
	var $item = null;

	function display($tpl = null)
	{
		$input = JFactory::getApplication()->input;

		$input->set( 'hidemainmenu', 1 );

		// load the jooDb object with table field infos
		$this->jb = JTable::getInstance( 'joodb', 'Table' );
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
            $this->item = new stdClass();
        }
		$text = ( $this->item ? JText::_( 'Edit' ) : JText::_( 'New' ) );
		JToolBarHelper::title(   $this->jb->name.': <small><small>['.$text.']</small></small>','database' );
		JToolBarHelper::apply('applydata');
		JToolBarHelper::save('savedata');
		JToolBarHelper::save2copy('savecopydata');
		JToolBarHelper::cancel('editdata.cancel');

		parent::display($tpl);
	}

}