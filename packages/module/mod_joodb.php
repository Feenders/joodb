<?php
/**
 * Displays a number of JooDatabase entries on a module position
 *
 * @package joodatabase
 * @subpackage module
 * @version 4.0
 * @author computer :: daten :: netze - feenders - Dirk Hoeschen
 * @link http://joodb.dirk-hoeschen.de
 * @copyright (C) 2012 feenders.de. all rights reserved
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

if (!file_exists(JPATH_SITE.'/components/com_joodb/helpers/subtemplate.php')) return false;
require_once(dirname(__FILE__).'/helper.php');
require_once(JPATH_SITE.'/components/com_joodb/helpers/joodb.php');

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx',''));
$pretext = $params->get('pretext');

JTable::addIncludePath(JPATH_SITE.'/administrator/components/com_joodb/tables');
$jb = JTable::getInstance( 'joodb', 'Table' );
if ($jb->load($params->get('joobase'))) {

	$query = " SELECT * FROM `".$jb->table."` ";

	if (!empty($jb->fstate)) $query .= " WHERE `".$jb->fstate."`>='1' ";

// Select by ordering the entries
	switch ($params->get('orderby'))  {
		case "fdate":
			$query .= " ORDER BY `".(!empty($jb->fdate) ? $jb->fdate : $jb->fid)."` DESC " ;
			break;
		case "fid":
			$query .= " ORDER BY `".$jb->fid."` DESC " ;
			break;
		case "random":
			$query .= " ORDER BY RAND() " ;
			break;
		case "ftitle":
			$query .= " ORDER BY `".$jb->ftitle."` ASC " ;
			break;
		default:
			$query .= " ORDER BY `".$jb->fid."` DESC " ;
	}

	$db = $jb->getTableDbo();
	$db->setQuery($query,0,$params->get('limit'));
	if ($items = $db->loadObjectList()) {
		require JModuleHelper::getLayoutPath('mod_joodb', $params->get('layout', 'default'));
	}
}
