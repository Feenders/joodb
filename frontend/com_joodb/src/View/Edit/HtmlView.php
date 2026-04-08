<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 *
 */

namespace Feenders\Component\Joodb\Site\View\Edit;


use Feenders\Component\Joodb\Site\Helper\JoodbHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use function defined;


// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML View class for the JooDatabase catalogs
 */
class HtmlView extends BaseHtmlView
{

	var $joobase = null;
	var $item = null;
	var $params = null;
	var $menu = null;

    public function display($tpl = null)
	{
		// Load the menu object and parameters
		// Get some objects from the JApplication
		$app = Factory::getApplication();
		$document = $app->getDocument();
		
        // Get the current menu item
        $menus	= $app->getMenu();
        $this->menu	= $menus->getActive();
		$this->params = $app->getParams();

		$model = $this->getModel();
		// read database configuration from joobase table
		$this->joobase = $model->getJoobase();

		//get the data page
		$this->item = $model->getData();

		$this->_prepareDocument();

 		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		JoodbHelper::prepareDocument();

		$app = Factory::getApplication();
		$document = $app->getDocument();

		if (!$this->params->get( 'page_title') )
			$this->params->set('page_title',	Text::_( $this->joobase->name ));

		if (!$this->params->get( 'page_heading' ) )
			$this->params->set('page_heading',Text::_( $this->joobase->name ));

		//set document title
		$document->setTitle( $this->params->get('page_title')." - ".$app->get('sitename') );

		if ($this->params->get('menu-meta_description'))
		{
			$document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		// load administrator language
		$app->getLanguage()->load('com_joodb' , JPATH_ADMINISTRATOR, $app->getLanguage()->getTag(), true);
	}

}
