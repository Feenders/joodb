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

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use function defined;

/**
 * Main Contoller
 */
class SubitemsController extends AdminController {

	public function save() {
		// Check for request forgeries.
		$this->checkToken();

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$table = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
		if (!$table->bind($_POST)) {
			throw new Exception($table->getError(),500);
		}
		if (!$table->check()) {
			throw new Exception($table->getError(),500);
		}
		if (!$table->store()) {
			throw new Exception($table->getError(),500);
		}

		header('Content-type: application/json');
		echo "success";
		die();
	}

	/**
	 * Display List of subentries
	 */
	public function getList() {
		$output = '<tr><td colspan="2">...</td>';
		$jbid= $this->input->getInt('jbid');
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$table = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
		if ($table->load( $jbid )) {
			$items = $table->getSubitems();
			if (!empty($items)) {
				$output=""; $k = 0;
				foreach ($items as $subitem) {
					$output .= '<tr class="row'.$k.'"><td>';
					$output .= '<a href="javascript:openSubtemplate('.$subitem->id.');void(0);" '
								.' title="'.Text::_("JDB_EDIT").'" ><span class="icon-edit"></span>'.Text::_("JDB_NAME").': '.$subitem->label.'</a><br/>';
					$output .= '<span class="small">'.Text::_("JDB_TABLE_IN_DATABASE").': '.$subitem->table.')</span></td>';
					$output .= '<td class="text-center"><a class="btn btn-sm btn-light" href="javascript: rmSubitem('.$subitem->id.');" ><span class="icon-trash"></span></a></td></tr>';
				$k = 1 - $k;
				}
			}
		}
		header("Content-Type: text/html; charset: utf-8");
		echo '<!-- xml version="1.0" encoding="utf-8" -->';
		echo $output;
		die();
	}

	/**
	 * Remove Item
	 */
	public function removeLine() {
		$db =  Factory::getContainer()->get(DatabaseInterface::class);
		if ($id = $this->input->getInt('id')) {
			$jbid = $this->input->getInt('jbid');
			$db->setQuery("DELETE  FROM `#__joodb_settings` WHERE `id` = ".$id." AND `jb_id` =".$jbid)->execute();
		}
		$this->getList();
	}

}
