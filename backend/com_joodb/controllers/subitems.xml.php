<?php
/** part of JooBatabase component - see http://joodb.feenders.de */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/**
 * Main Contoller
 */
class JooDBControllerSubitems extends JooDBController
{
	/**
	 * Constructor
	 */
	public function __construct( $config = array() )
	{
		parent::__construct( $config );
	}

	public function save() {
		// Initialize variables
		$row = JTable::getInstance('subitem', 'Table');
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		if (!$row->bind($_POST)) {
			throw new Exception($row->getError(),500);
		}
		if (!$row->check()) {
			throw new Exception($row->getError(),500);
		}
		if (!$row->store()) {
			throw new Exception($row->getError(),500);
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
		$table = JTable::getInstance( 'joodb', 'Table' );
		if ($table->load( $jbid )) {
			$items = $table->getSubitems();
			if (!empty($items)) {
				$output=""; $k = 0;
				foreach ($items as $subitem) {
					$output .= '<tr class="row'.$k.'"><td>';
					$output .= '<a href="javascript:openSubtemplate('.$subitem->id.');void(0);" '
								.' title="'.JText::_('Edit').'" ><span class="icon-edit"></span>'.JText::_('Name').': '.$subitem->label.'</a><br/>';
					$output .= '<span class="small">'.JText::_('Table in Database').': '.$subitem->table.')</span></td>';
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
		$db = JFactory::getDBO();
		if ($id = $this->input->getInt('id')) {
			$jbid = $this->input->getInt('jbid');
			$db->setQuery("DELETE  FROM `#__joodb_settings` WHERE `id` = ".$id." AND `jb_id` =".$jbid)->execute();
		}
		$this->getList();
	}

}
