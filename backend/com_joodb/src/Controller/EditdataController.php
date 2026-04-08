<?php
/**
 * @version     5.0.0
 * @package     com_joodb
 * @copyright   Copyright (C) 2011-2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Dirk Hoeschen - Feenders <hoeschen@feenders.de> - http://www.feenders.de
 */
namespace Feenders\Component\Joodb\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use function defined;
use function is_null;

/**
 * Main Contoller
 */
class EditdataController extends AdminController {

	var $view_list = 'listdata';
	var $default_view = 'editdata';

	public function cancel($key = null)
	{
		$url = 'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend();

		// Check if there is a return value
		$return = $this->input->get('return', null, 'base64');

		if (!is_null($return) && Uri::isInternal(base64_decode($return)))
		{
			$url = base64_decode($return);
		}

		// Redirect to the list screen.
		$this->setRedirect(Route::_($url, false),Text::_("JDB_EDIT_CANCELED") );

		return true;
	}

	public function edit()
	{
		$this->input->set('view', 'editdata');
		parent::display();
	}

}