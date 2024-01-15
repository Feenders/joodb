<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
require_once( JPATH_COMPONENT_ADMINISTRATOR.'/helpers/form.php' );

/**
 * HTML Edit class for the JooDatabase single element
 */
class JoodbViewEdit extends JViewLegacy
{

	var $joobase = null;
	var $item = null;
	var $params = null;
	var $menu = null;

    public function display($tpl = null)
	{
		// Load the menu object and parameters
		// Get some objects from the JApplication
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		
        // Get the current menu item
        $menus	= $app->getMenu();
        $this->menu	= $menus->getActive();
		$this->params = $app->getParams();

		//get the data page
		$this->item = $this->get('data');

		// read database configuration from joobase table
		$this->joobase = $this->get('joobase');

		if (!$this->params->get( 'page_title' ) )
			$this->params->set('page_title', JText::_( $this->joobase->name ));

		if (!$this->params->get( 'page_heading' ) )
			$this->params->set('page_heading',JText::_( $this->joobase->name ));

		//set document title			
		$document->setTitle( $this->joobase->name." - ".$app->get('sitename') );

		//set pathway
		$pathway  = $app->getPathway();
		$pathway->addItem(JoodbHelper::wrapText($this->joobase->name,20), '');

        // load administrator language
        $language = JFactory::getLanguage();
        $language->load('com_joodb' , JPATH_ADMINISTRATOR, $language->getTag(), true);

		JoodbHelper::prepareDocument();

        JHtml::_('behavior.keepalive');
        JHtml::_('behavior.formvalidator');
        if ((int)JVERSION>=3) JHtml::_('formbehavior.chosen', 'select');

		parent::display($tpl);
	}
}
