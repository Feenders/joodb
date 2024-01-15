<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_joodb'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

// Set the table directory
JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/tables/');

// Require the helper library
require_once( JPATH_COMPONENT_ADMINISTRATOR.'/helpers/joodb.php' );
JoodbAdminHelper::prepareDocument();

// Load the controller
require_once (JPATH_COMPONENT_ADMINISTRATOR.'/controller.php');

// Create the controller
jimport('joomla.application.component.controller');
$controller = JControllerLegacy::getInstance('Joodb');
// Perform the Request task
$controller->execute(JFactory::getApplication()->input->get('task'));
// Redirect if set by the controller
$controller->redirect();
