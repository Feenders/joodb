<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

namespace Feenders\Component\Joodb\Site\Dispatcher;

defined('_JEXEC') or die();

use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Factory;
use function defined;

require_once JPATH_LIBRARIES . '/loader.php';


/**
 * ComponentDispatcher class for COM_DATA
 *
 * @since  1.0.0
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * Dispatch a controller task. Redirecting the user if appropriate.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function dispatch()
    {
		parent::dispatch();
    }
}
