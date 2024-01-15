<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
require_once(JPATH_COMPONENT_ADMINISTRATOR.'/assets/SpreadsheetAutoload.php');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * HTML View class for the JooDatabase cataloges
 */
class JoodbViewCatalog extends JViewLegacy
{
	var $joobase = null;
	var $items = null;
	var $params = null;

	public function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$this->params = $app->getParams();
		// read database configuration from joobase table
		$this->joobase =  $this->get('joobase');
		//get the data items
		$this->items = $this->get('export');
		$fields = $this->getExportFields();
		if (empty($fields)) {
			throw new Exception("No fields to export",500);
		}

		/** generate excel spreadsheet */
		$objXLS = new Spreadsheet();
		$objXLS->getProperties()->setCreator("JooDatabase (joodb.feenders.de)")
		       ->setLastModifiedBy("JooDatabase (joodb.feenders.de)")
		       ->setTitle("Online Excel export of ".$this->joobase->name)
		       ->setSubject("Online Excel export of ".$this->joobase->name)
		       ->setDescription("Generated on ".addslashes($app->get( 'sitename' ))." : Export of current selection")
		       ->setKeywords("export, database")
		       ->setCategory("Exportfile");

		$objXLS->setActiveSheetIndex(0);
		$row = 1; $cols = array(); $n=1;
		$styleArray = array(
			'borders' => array(
				'allborders' => array(
					'style' => Border::BORDER_THIN
				)
			)
		);
		$objXLS->getDefaultStyle()->applyFromArray($styleArray);
		foreach ($fields as $field => $type) {
			$objXLS->getActiveSheet()->setCellValueByColumnAndRow($n,1, ucfirst($field));
			$cols[] = $objXLS->getActiveSheet()->getCellByColumnAndRow($n,1)->getColumn();
			if ($type=="text") {
				$objXLS->getActiveSheet()->getColumnDimension(end($cols))->setWidth(100);
			} else if ($type=="varchar") {
				$objXLS->getActiveSheet()->getColumnDimension(end($cols))->setWidth(30);
			}
			$n++;
		}

		$objXLS->getActiveSheet()->getStyle('A1:'.end($cols).'1')->getFill()->setFillType(Fill::FILL_SOLID);
		$objXLS->getActiveSheet()->getStyle('A1:'.end($cols).'1')->getFill()->getStartColor()->setARGB('FFFFEF83');
		$objXLS->getActiveSheet()->getStyle('A'.$row.':'.end($cols).'1')->applyFromArray($styleArray);
		// Fill Data Rows
		if (!empty($this->items)) {
			foreach ($this->items as $item) {
				$n=0; $row++;
				foreach ($fields as $field => $type) {
					$objXLS->getActiveSheet()->setCellValue($cols[$n].$row, strip_tags($item->{$field}));
					$n++;
				}
				$rcol = (($row%2)==0) ? "FFFFFFFF" : "FFF0F0F0";
				$objXLS->getActiveSheet()->getStyle('A'.$row.':'.end($cols).$row)->getFill()->setFillType(Fill::FILL_SOLID);
				$objXLS->getActiveSheet()->getStyle('A'.$row.':'.end($cols).$row)->getFill()->getStartColor()->setARGB($rcol);
			}
		}

		$xlfile = JFilterOutput::stringURLSafe($this->joobase->name)."_".date('Y-m-d_H-m');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$xlfile.'.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = IOFactory::createWriter($objXLS, 'Xlsx');
		$objWriter->save('php://output');
		$app->close();
	}

	/**
	 * Get fields for export
	 */
	private function getExportFields()
	{
		$subitems =  $this->joobase->getSubitems();

		$ef = $this->params->get('exportfields');
		if (!empty($ef)) {
			$fields = array();
			$fn = preg_split("/,/", $ef);
			foreach ($fn as $f) {
				$f = trim($f);
				if (isset($this->joobase->fields[$f])) {
					$fields[$f] = $this->joobase->fields[$f];
				} else if (isset($subitems[$f])) {
					$fields[$f] = 'text';
					foreach ($this->items as &$item) {
						$item->{$f} = $this->getSubitemValue($item,$subitems[$f]);
					}
				}
			}
		} else {
			$fields = $this->joobase->fields;
			foreach ($subitems AS $name => $subitem) {
				$fields[$name] = 'text';
				foreach ($this->items as &$item) {
					$item->{$name} = $this->getSubitemValue($item,$subitems[$name]);
				}
			}
		}
		return $fields;
	}

	/**
	 * Get Name Field os Subitems and return list
	 *
	 * @param $item
	 * @param $subitem
	 *
	 * @return string
	 *
	 * @since version
	 */
	private function getSubitemValue($item,$subitem) {
		$joobase = &$this->joobase;
		$db = $joobase->getTableDBO();
		$query	= "SELECT a.`".$subitem->name_field."` FROM `".$subitem->table."` AS a ";
		switch ($subitem->type) {
			case '1' :
				$query .= " WHERE a.`".$subitem->id_field."`='".$item->{$joobase->fid}."' ";
				break;
			case '2' :
				$query .= " LEFT JOIN `".$subitem->idx_table."` AS c ON c.`".$subitem->idx_id2."`=a.`".$subitem->id_field."` ";
				$query .= " WHERE c.`".$subitem->idx_id1."`='".$item->{$joobase->fid}."' ";
				break;
			case '3' :
				$query .= " WHERE a.`".$subitem->id_field."`='".$item->{$joobase->fid}."' ";
				break;
			case '4' :
				$query .= " WHERE a.`".$subitem->id_field."`='".$item->{$subitem->idx_sub}."' ";
				break;
		}
		$query .= " ORDER BY a.`".$subitem->name_field."` ASC ";
		$il = $db->setQuery($query)->loadColumn();
		return join(", ",$il);
	}

}

