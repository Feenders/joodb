<?php
/**
 * @version     1.0.0
 * @package     com_joodb
 * @copyright   Copyright (C) 2021. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Dirk Hoeschen - Feenders <hoeschen@feenders.de> - http://www.feenders.de
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;

/**
 * Banners list controller class.
 *
 * @since  1.6
 */
class joodbControllerEditdata extends JControllerForm
{

	function __construct() {
		$this->view_list = 'listdata';
		parent::__construct();
	}

	public function cancel($key = null)
	{
		$url = 'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend();

		// Check if there is a return value
		$return = $this->input->get('return', null, 'base64');

		if (!\is_null($return) && Uri::isInternal(base64_decode($return)))
		{
			$url = base64_decode($return);
		}

		// Redirect to the list screen.
		$this->setRedirect(Route::_($url, false));

		return true;
	}


}