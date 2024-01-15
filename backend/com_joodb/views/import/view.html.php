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

class JoodbViewImport extends JViewLegacy
{
	var $bar = null;
	var $version = null;

	function display($tpl = null)
	{
		$this->version = new JVersion();
		$this->bar = JToolBar::getInstance('toolbar');
	 	JToolBarHelper::title(JText::_( "Import" ), 'cpanel.png' );
		$this->bar->appendButton('Standard', 'arrow-right', JText::_( "Go" ), 'import',false);

        JHTML::_('behavior.formvalidator');

		parent::display($tpl);


	}

}