<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

namespace Feenders\Component\Joodb\Administrator\View\Info;

use Feenders\Component\Joodb\Administrator\Helper\JoodbAdminHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use function defined;


// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
{

    protected $version = 1;

	function display($tpl = null) {

        ToolbarHelper::title(   Text::_( "JooDatabase" ).': <small><small>['.Text::_( 'Information' ).']</small></small>','info' );

        $bar = Factory::getApplication()->getDocument()->getToolbar('toolbar');
		$bar->appendButton('Help', 'http://joodb.feenders.de/support.html', false, 'http://joodb.feenders.de/support.html', null);

		$db	= Factory::getContainer()->get(DatabaseInterface::class);
		$db->setQuery("SELECT value FROM `#__joodb_settings` WHERE `name` = 'version' AND `jb_id` IS NULL",0,1);
		$this->version = $db->loadResult();
        parent::display($tpl);
    }
}
