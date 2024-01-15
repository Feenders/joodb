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

class JoodbViewSubitem extends JViewLegacy
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
		$app = JFactory::getApplication();
		$this->config = JComponentHelper::getParams('com_joodb');		
		$this->version = new JVersion();
		$document = JFactory::getDocument();

		$this->bar =  JToolBar::getInstance('toolbar');
		$id = $app->input->getCmd('id');
		$text = ( $id ? JText::_( 'Edit' ) : JText::_( 'New' ) );
	 	JToolBarHelper::title(JText::_( "Linked Tables" ).': <small><small>['.$text.']</small></small>', 'joodb.png' );
	 	JToolBarHelper::save('save');
	 	JToolBarHelper::cancel('close');

		$document->addStyleSheet('components/com_joodb/assets/singleview.css');
				
		$this->item = JTable::getInstance( 'subitem', 'Table' );
		$this->item->load( $id );
		
		$this->joobase = JTable::getInstance( 'joodb', 'Table' );
		$this->joobase->load($app->input->getInt('jbid'));
		$tdb = $this->joobase->getTableDBO();
		$this->tables = $tdb->getTableList();		

		$tdb->setQuery("SHOW COLUMNS FROM `".$this->joobase->table."`");
		$this->fields = $tdb->loadObjectList();

		JHtml::_('jquery.framework');
        JHTML::_('behavior.formvalidator');
        JHtml::_('behavior.keepalive');
		JHtml::_('bootstrap.tooltip', '.hasTooltip');

		parent::display($tpl);


	}

}