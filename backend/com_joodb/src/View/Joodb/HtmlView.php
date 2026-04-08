<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

namespace Feenders\Component\Joodb\Administrator\View\Joodb;

use Feenders\Component\Joodb\Administrator\Helper\JoodbAdminHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use function defined;


// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
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

		if (empty($this->items)) { $this->isEmptyState = true; }

		parent::display($tpl);

	}


	/**
	 * Add the page title and toolbar.
	 *
	 * @since   4.0
	 */
	protected function addToolbar() {
		$text = Text::_("JDB_DATABASES");
		ToolBarHelper::title(Text::_("JooDatabase") . ': <small><small>[' . $text . ']</small></small>', 'database');

		$bar = Factory::getApplication()->getDocument()->getToolbar('toolbar');
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

		$childBar->publish('publish')
			->text('JTOOLBAR_PUBLISH')
			->listCheck(true);

		$childBar->unpublish('unpublish')
			->text('JTOOLBAR_UNPUBLISH')
			->listCheck(true);

		$childBar->delete('remove')
			->text('JTOOLBAR_DELETE')
			->icon('icon-trash')
			->message('JDB_REALLY_DELETE')
			->listCheck(true);

		JoodbAdminHelper::getPopupButton('upload', 'JDB_IMPORT', 'index.php?option=com_joodb&amp;tmpl=component&amp;view=import', 680, 480);
		ToolBarHelper::preferences('com_joodb');
		$bar->appendButton('Help', 'http://joodb.feenders.de/support.html', false, 'http://joodb.feenders.de/support.html', null);
	}
}
