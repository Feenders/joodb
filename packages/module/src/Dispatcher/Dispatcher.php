<?php
/**
 * Displays a number of JooDatabase entries on a module position
 *
 * @package joodatabase
 * @subpackage module
 * @version 5.0
 * @author computer :: daten :: netze - feenders - Dirk Hoeschen
 * @link http://joodb.dirk-hoeschen.de
 * @copyright (C) 2012 - 2025 feenders.de. all rights reserved
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Feenders\Module\Joodb\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Table\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_joodb
 */
class Dispatcher extends AbstractModuleDispatcher {

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   5.1.0
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
	    $params = &$data['params'];

	    $jb = Table::getInstance('JoodbTable', '\\Feenders\\Component\\Joodb\\Administrator\\Table\\');
		 if ($jb->load((int)$params->get('joobase','1')))
	    {

			$query = " SELECT * FROM `" . $jb->table . "` ";

		    if (!empty($jb->fstate)) $query .= " WHERE `" . $jb->fstate . "`>='1' ";

		    // Select by ordering the entries
		    switch ($params->get('orderby'))
		    {
			    case "fdate":
				    $query .= " ORDER BY `" . (!empty($jb->fdate) ? $jb->fdate : $jb->fid) . "` DESC ";
				    break;
			    case "fid":
				    $query .= " ORDER BY `" . $jb->fid . "` DESC ";
				    break;
			    case "random":
				    $query .= " ORDER BY RAND() ";
				    break;
			    case "ftitle":
				    $query .= " ORDER BY `" . $jb->ftitle . "` ASC ";
				    break;
			    default:
				    $query .= " ORDER BY `" . $jb->fid . "` DESC ";
		    }

		    $db = $jb->getTableDbo();
		    $db->setQuery($query, 0, $params->get('limit','10'));
		    $data['items'] = $db->loadObjectList();
		    $data['jb'] = &$jb;

		}
	    return $data;

    }
}
