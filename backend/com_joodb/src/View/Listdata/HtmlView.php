<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

namespace Feenders\Component\Joodb\Administrator\View\Listdata;

use Feenders\Component\Joodb\Administrator\Helper\JoodbAdminHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Version;
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

	protected $items;
	protected $jb;
	protected $pagination;
	protected $state;
	protected $isEmptyState = false;

	function display($tpl = null)
	{
		$model = $this->getModel();
		$this->state		= $model->getState();
		$this->items		= $model->getItems();
		$this->pagination	= $model->getPagination();
		$this->jb	        = $model->getJb();

		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();


		if (empty($this->items)) { $this->isEmptyState = true; }
		$this->addToolbar();

		parent::display($tpl);

	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since   4.0
	 */
	protected function addToolbar() {
		ToolBarHelper::title(   $this->jb->name.': <small><small>['.Text::_("JDB_EDIT_DATA").']</small></small>','database' );
		ToolBarHelper::addNew('editdata.edit');
		ToolBarHelper::editList('editdata.edit');
		ToolBarHelper::deleteList('JDB_REALLY_DELETE','removedata');
		ToolBarHelper::cancel('cancel' ,'JDB_CLOSE');
	}

}
