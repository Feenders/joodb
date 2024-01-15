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
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;

/**
 * Banners list controller class.
 *
 * @since  1.6
 */
class joodbControllerJoodb extends JooDBController
{

	public function __construct($config = array())
	{
		parent::__construct($config);
	}


}