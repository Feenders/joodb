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


/**
 * HTML View class for the JooDatabase single element
 */
class JoodbViewArticle extends JViewLegacy
{
	var $joobase = null;
	var $item = null;
	var $params = null;
	var $can_edit = false;
	
	public function display($tpl = null)
	{

		// Get some objects from the JApplication
		$app = JFactory::getApplication();
		$this->params = $app->getParams();

		//get the data page
		$this->item = $this->get('data');

		// read database configuration from joobase table
		$this->joobase = $this->get('joobase');
		
		$this->_prepareDocument();
		
		// check if article is editable
		$jparams = new JRegistry( $this->joobase->params );		
		if ($jparams->get("accesse","1")==1) {
			$user = JFactory::getUser();
			if (method_exists('JUser', 'getAuthorisedViewLevels')) {
				$groups	= $user->getAuthorisedViewLevels();
				$this->can_edit = in_array(3, $groups);
			} else {
				$this->can_edit = ($user->aid>=2) ? true : false;
			}
		}

		parent::display($tpl);
	}
	
	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$pathway = $app->getPathway();

		if (!$this->params->get( 'page_title' ) )
			$this->params->set('page_title', JText::_($this->joobase->name ));
		
		if (!$this->params->get( 'page_heading' ) )
			$this->params->set('page_heading', JText::_($this->joobase->name ));		
		
		// we dont want to link title fields in single view
		$this->params->set('link_titles',false);		
		
		//set document title
		$document->setTitle( $this->item->{$this->joobase->ftitle}." - ".$this->params->get('page_title')." - ".$app->get('sitename') );
		//set pathway
		$pathway->addItem(JoodbHelper::wrapText($this->item->{$this->joobase->ftitle},20), '');

		if ($this->params->get('menu-meta_description'))
		{
			$document->setDescription($this->params->get('menu-meta_description'));
		}
		
		if ($this->params->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		JoodbHelper::prepareDocument();

	}
}

