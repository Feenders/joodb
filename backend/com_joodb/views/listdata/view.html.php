<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

// no direct access
defined('_JEXEC') or die();


class joodbViewListData extends \Joomla\CMS\MVC\View\HtmlView
{
	protected $items;
	protected $jb;
	protected $pagination;
	protected $state;
	protected $isEmptyState = false;

	function display($tpl = null)
	{
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->jb	= $this->get('jb');

		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');


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
		JToolBarHelper::title(   $this->jb->name.': <small><small>['.JText::_( 'Edit Data' ).']</small></small>','database' );
		JToolBarHelper::addNew('editdata');
		JToolBarHelper::editList('editdata');
		JToolBarHelper::deleteList('Really Delete','removedata');
		JToolBarHelper::cancel('cancel' ,'close');
	}

}
