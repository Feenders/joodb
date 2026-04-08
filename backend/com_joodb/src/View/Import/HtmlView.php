<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

namespace Feenders\Component\Joodb\Administrator\View\Import;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Version;
use function defined;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
{
	var $bar = null;
	var $version = null;

	function display($tpl = null)
	{
		$this->version = new Version();
		$this->bar = Factory::getApplication()->getDocument()->getToolbar('toolbar');
	 	ToolBarHelper::title(Text::_( "JDB_IMPORT" ), 'table' );
		$this->bar->appendButton('Standard', 'arrow-right', Text::_("JDB_GO"), 'import',false);

        HtmlHelper::_('behavior.formvalidator');

		parent::display($tpl);


	}

}