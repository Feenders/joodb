<?php
/**
 * mod_joodb helper class
 * 
 * @package joodatabase
 * @subpackage module
 * @author computer :: daten :: netze - feenders - Dirk Hoeschen
 * @link http://joodb.dirk-hoeschen.de
 * @copyright (C) 2012 feenders.de. all rights reserved
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * mod_joodbHelper class
 */
class modJoodbHelper
{
	/**
	 * Try to get Itemid from menu and route to item
	 *
	 * @param misc $jb
	 * @param int $id
	 * @return string url to item
	 */
	public static function getRoute(&$jb,&$item)
	{
		$app = JFactory::getApplication();
		// Try to estimate the Itemid if we are outside of the database
		if (empty($jb->Itemid)) {
			$db = JFactory::getDbo();
			$db->setQuery("SELECT id FROM #__menu WHERE published=1 "
					." AND link LIKE 'index.php?option=com_joodb&view=catalog%'"
					." AND ( params LIKE 'joobase=".$jb->id."%'"
					." OR params LIKE '{\"joobase\":\"".$jb->id."\"%' )");
			$jb->Itemid=$db->loadResult();
		}
		$catalog = (!empty($jb->Itemid)) ? "&Itemid=".$jb->Itemid : "";
		$falias=$jb->getSubdata('falias');
		if (!empty($app->get('sef')) && !empty($falias) && !empty($item->{$falias})) {
			$slug = $item->{$falias};
		} else {
			$slug = $item->{$jb->fid}.':'.JFilterOutput::stringURLSafe($item->{$jb->ftitle});
		}
		return JRoute::_('index.php?option=com_joodb&view=article&joobase='.$jb->id.'&id='.$slug.$catalog,false);
	}
	
}
