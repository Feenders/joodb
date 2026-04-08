<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 *
 */

namespace Feenders\Component\Joodb\Site\View\Article;


use Feenders\Component\Joodb\Site\Helper\JoodbHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Registry\Registry;
use function defined;


// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML View class for the JooDatabase single element
 */
class HtmlView extends BaseHtmlView
{
	var $joobase = null;
	var $item = null;
	var $params = null;
	var $can_edit = false;
	
	public function display($tpl = null)
	{

		// Get some objects from the JApplication
		$app = Factory::getApplication();
		$this->params = $app->getParams();

		$model = $this->getModel();
		// read database configuration from joobase table
		$this->joobase = $model->getJoobase();

		//get the data page
		$this->item = $model->getData();


		$this->_prepareDocument();
		
		// check if article is editable
		$jparams = new Registry( $this->joobase->params );		
		if ($jparams->get("accesse","1")==1) {
			$user = $app->getIdentity();
			$groups	= $user->getAuthorisedViewLevels();
			$this->can_edit = in_array(3, $groups);
		}

		parent::display($tpl);
	}
	
	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		$app = Factory::getApplication();
		$document = $app->getDocument();
		$pathway = $app->getPathway();

		if (!$this->params->get( 'page_title' ) )
			$this->params->set('page_title', Text::_($this->joobase->name ));
		
		if (!$this->params->get( 'page_heading' ) )
			$this->params->set('page_heading', Text::_($this->joobase->name ));		
		
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

