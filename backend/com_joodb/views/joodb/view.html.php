<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

// no direct access
defined('_JEXEC') or die();

class JoodbViewJoodb extends \Joomla\CMS\MVC\View\HtmlView
{
	protected $items;
	protected $pagination;
	protected $state;
	protected $isEmptyState = false;

	function display($tpl = null)
	{
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');

		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->addToolbar();

		if (!\count($this->items)) { $this->isEmptyState = true; }

		parent::display($tpl);

	}


	/**
	 * Add the page title and toolbar.
	 *
	 * @since   4.0
	 */
	protected function addToolbar() {
		$text = JText::_('Databases');
		JToolBarHelper::title(JText::_("JooDatabase") . ': <small><small>[' . $text . ']</small></small>', 'database');

		$bar = JToolBar::getInstance('toolbar');
		JoodbAdminHelper::getPopupButton('new', 'JTOOLBAR_NEW', 'index.php?option=com_joodb&amp;tmpl=component&amp;view=joodbentry&amp;layout=step1&amp;task=addnew', 680, 400);

		$dropdown = $bar->dropdownButton('status-group')
			->text('JTOOLBAR_CHANGE_STATUS')
			->toggleSplit(false)
			->icon('icon-ellipsis-h')
			->buttonClass('btn btn-action')
			->listCheck(true);

		$childBar = $dropdown->getChildToolbar();

		$childBar->standardButton('edit')
			->text("JTOOLBAR_EDIT")
			->icon('icon-edit')
			->task('edit')
			->listCheck(true);

		$childBar->publish('archive')
			->text('JTOOLBAR_PUBLISH')
			->listCheck(true);

		$childBar->unpublish('unpublish')
			->text('JTOOLBAR_UNPUBLISH')
			->listCheck(true);

		$childBar->delete('remove')
			->text('JTOOLBAR_DELETE')
			->icon('icon-trash')
			->message('Really delete')
			->listCheck(true);

		JoodbAdminHelper::getPopupButton('upload', 'IMPORT', 'index.php?option=com_joodb&amp;tmpl=component&amp;view=import', 680, 480);
		JToolBarHelper::preferences('com_joodb');
		$bar->appendButton('Help', 'http://joodb.feenders.de/support.html', false, 'http://joodb.feenders.de/support.html', null);
	}
}
