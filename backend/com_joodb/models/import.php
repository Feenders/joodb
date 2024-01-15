<?php
/**
 * @file import.php created 28.07.2011, 12:23:47
 * @package		Joomla
 * @author	feenders - dirk hoeschen (hoeschen@feenders.de)
 * @abstract	custom component for client
 * @link	http://www.feenders.de
 * @copyright	Copyright (C) 2011 computer daten netze :: feenders
 * @license		CC-GNU-LGPL
 * @version  1.0
 */

// no direct access
defined('_JEXEC') or die('Restricted access');


require_once(JPATH_COMPONENT_ADMINISTRATOR.'/assets/SpreadsheetAutoload.php');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class joodbModelImport extends JModelLegacy {

	var $columns = array();
	var $has_column_names = 1;
	var $enclosure = "";
	var $delimeter = "";
	var $tablename = "";
	var $file = "";
	var $format = false;
	var $querycache = "";
	var $errors = array();
	var $finished = false;
	var $highestRow = 0;
	var $chunksize = 200;
	var $startRow = 0;


	/**
	 * Contructor
	 * @param array $config
	 */
	function __construct($config) {
		$app = JFactory::getApplication();
		$this->has_column_names = $app->input->getInt("has_column_names",1);
		$this->tablename = $app->input->getCmd("tablename","new_table");
		$this->enclosure = $app->input->getString("enclosure","\"");
		$this->delimeter = $app->input->getString("delimeter",";");
		parent::__construct($config);
	}

	/**
	 * Update dataset
	 * @param $id int id des datensatzes
	 * @param $item object
	 * @param $table string
	 */
	private function saveEntry($id, $item){
		$db = $this->getDbo();
		$savestring = "";
		foreach ($item as $field => $value) {
			$savestring .= " `".$field."` = ".(($value!="") ? "'".$db->escape($value)."'" : "NULL").",";
		}
		$query = "UPDATE `".$this->tablename."` SET " . substr($savestring,0,-1) . " WHERE id = $id";
		$db->setQuery($query);
		try {
			$db->execute();
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Add new data
	 * @param object $item
	 */
	private function addEntry($item,$ferstellt="created"){
		$db = JFactory::getDbo();
		$entry = new stdClass();
		foreach ($item as $field => $value) {
			$entry->{$field} = (empty($value)) ? NULL : $db->escape($value);
		}
		if(!empty($ferstellt) && empty($entry->{$ferstellt})) {
			$entry->{$ferstellt} = date("Y-m-d H:i:s");
		}
		return ($db->insertObject($this->tablename,$entry));
	}


	/**
	 * Set column titles and types by analyzing first row ...
	 * @param misc $sw
	 * @return boolean true or false
	 */
	private function generateTable(&$ws) {
		if (!$row=$ws->getRowIterator()->current()) return false;
		$cellIterator = $row->getCellIterator();
		$cellIterator->setIterateOnlyExistingCells(true);
		$genfields = array("id"=>"`id` int(11) NOT NULL AUTO_INCREMENT","state"=>"`state` BOOLEAN NOT NULL DEFAULT '1'","alias"=>"`alias` varchar(255) NULL DEFAULT NULL","created"=>"`created` datetime NULL DEFAULT NULL");

		foreach ($cellIterator as $i => $cell)
			if (!is_null($cell)) {
				$this->columns[$i] = new stdClass();
				// get column names
				if ($this->has_column_names==1) {
					$this->columns[$i]->title = str_replace(" ","_",preg_replace("/[^a-zA-Z0-9_\- ]/", "", $cell->getCalculatedValue()));
					$n = (strtolower($this->columns[$i]->title));
					if (isset($genfields[$n])) unset($genfields[$n]);
				} else {
					$this->columns[$i]->title = "column_".sprintf( '%03d', $i);
				}
				// get cell row 2 and type / value
				$dcell = $ws->getCell($cell->getColumn()."2");
				$this->columns[$i]->size = 0;
				$this->columns[$i]->type = $dcell->getDataType();
				$this->columns[$i]->format = $ws->getStyle($dcell->getCoordinate())->getNumberFormat()->getFormatCode();
				$f = strtolower($this->columns[$i]->format);
				if ($this->columns[$i]->type=="s" || \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($dcell)) {
					if (strpos($f,"yy")!==false) {
						$this->columns[$i]->format = (strpos($f,"h")!==false) ? "Y-m-d h:m:s" : "Y-m-d";
						$this->columns[$i]->type = (strpos($f,"h")!==false) ? "datetime DEFAULT NULL" : "date DEFAULT NULL";
					} else if (strpos($f,"hh")!==false) {
						$this->columns[$i]->format = "h:m:s";
						$this->columns[$i]->type = "time DEFAULT NULL";
					} else $this->columns[$i]->type =  "text";
				} else if ($this->columns[$i]->type=="n") {
					if ($f=="general") {
						$this->columns[$i]->type = "text";
					} else {
						if($f[0]=='0') {
							$this->columns[$i]->type = "int(8)";
						} else {
							$this->columns[$i]->type = "float";
						}
					}
				} else if ($this->columns[$i]->type=="b") {
					$this->columns[$i]->type =  "tinyint(1) DEFAULT 1";
				} else	$this->columns[$i]->type = "text";
				if ($this->columns[$i]->format=="@") $this->columns[$i]->type =  "text";
			}
		$db = $this->getDbo();
		// if the table has the same size - clear only
		$db->setQuery("SHOW COLUMNS FROM `".$this->tablename."`");
		$gentable = true;
		try {
			$fields = $db->loadObjectList();
			$sum_columns = count($this->columns);
			$sum_columns =  $sum_columns * count($genfields);
			if (count($fields) == $sum_columns) {
				$db->setquery("TRUNCATE `".$this->tablename."`;");
				$gentable = false;
			} else {
				$db->setquery("DROP TABLE IF EXISTS `".$this->tablename."`;");
			}
			$db->execute();
		} catch (RuntimeException $e) { }
		// generate table
		if ($gentable) {
			foreach ($this->columns AS $n => $column) {
				$genfields[$column->title] = "`".$column->title."` ".$column->type;
			}
			$query = "CREATE TABLE IF NOT EXISTS `".$this->tablename."` (";
			$query .= join(",",$genfields);
			$query .= ",PRIMARY KEY (`id`)) ENGINE=INNODB DEFAULT CHARSET=utf8";
			$db->setquery($query);
			try {
				$db->execute();
			} catch (RuntimeException $e) {
				$this->setError($e->getMessage());
				return false;
			}
		}
		return true;
	}

	/**
	 * Set own error message
	 * @message string
	 */
	public function setError($message) {
		$this->errors[] = $message;
	}

	/**
	 * Import spredsheet into a table
	 * @param sting $file
	 */
	public function importSheet($file) {
		$app = JFactory::getApplication();
		$this->file = $app->get('tmp_path')."/jbtableimport";
		// move to temp jbimport to allow chunk import
		jimport('joomla.filesystem.file');
		if (!JFile::upload($file['tmp_name'], $this->file)) {
			$this->setError("Unable to create local file. Please check Joomla temp path.");
			return false;
		}

		// find format from filetype
		$ext = strtolower(strrchr($file['name'],"."));
		switch ($ext) {
			case ".xml":
				$this->format = "Xml";
				break;
			case ".xlsx":
				$this->format = "Xlsx";
				$or = IOFactory::createReader($this->format);
				if (!$or->canRead($this->file)) $this->format = "Ods";
				break;
			case ".xls":
				$this->format = "Xls";
				break;
			case ".csv":
				$this->format = "Csv";
				break;
			case ".ods":
				$this->format = "Ods";
				break;
			default:
				$this->format = false;
		}
		if ($this->format!=false) {
			$or = IOFactory::createReader($this->format);
			if ($this->format=="Csv") {
				$or->setEnclosure($this->enclosure);
				$or->setDelimiter($this->delimeter);
			} else {
				$or->setReadDataOnly(false);
				$or->setLoadSheetsOnly(true);
			}
			$oe = $or->load($this->file);
			$ws = $oe->getActiveSheet();
			if ($this->format=="CSV") {
				// @todo: not propper but csv-reader does not count the total lines
				$sheet=file($this->file);
				$this->highestRow  = count($sheet);
				unset($sheet);
			} else {
				$this->highestRow = $ws->getHighestRow();
			}
			if ($this->generateTable($ws)) $this->message = JText::_("Table created");
			$this->startRow = ($this->has_column_names==0) ? 1 : 2;
		} else {
			$this->setError(JText::_("ERROR_IMPORTING_TABLE")." : ".JText::_("UPLOAD_VALID_EXCEL_FILE"));
			return false;
		}
		return true;
	}

	/**
	 * Import the next X rows. Prevent timeout and memoryproblems
	 */
	public function importChunk() {
		// get the sheet
		try {

			$or = IOFactory::createReader($this->format);
			if ($this->format=="CSV") {
				$or->setEnclosure($this->enclosure);
				$or->setDelimiter($this->delimeter);
			} else {
				$or->setReadDataOnly(false);
				$or->setLoadSheetsOnly(true);
			}


			$chunkFilter = new ChunkReadFilter();
			$or->setReadFilter($chunkFilter);

			$chunkFilter->setRows($this->startRow,$this->chunksize);
			$ss = $or->load($this->file);
			$ws = $ss->getActiveSheet();

			$endRow = $this->startRow+$this->chunksize;

			while ($this->startRow<$endRow) {
				$item = array();
				foreach ($this->columns as $column => &$cval) {
					$cell = $ws->getCell($column.$this->startRow,false);
					if (!is_null($cell) && isset($cval["title"])) {
						if ($cval['type']=="text") {
							$item[$cval["title"]] = $cell->getFormattedValue();
							$sl = strlen($item[$cval["title"]]);
							if ($sl>$cval['size']) {
								$cval['size'] = $sl;
							}
						} else {
							if ($cval['type']=="float" || $cval['type']=="int(8)") {
								$item[$cval["title"]] = $cell->getValue();
								$item[$cval["title"]] = preg_replace("/[^0-9\.]/","",$item[$cval["title"]]);
							} else {
								$value = $cell->getValue();
								if (!empty($value)) {
									$ts = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value);
									$item[$cval["title"]] = date($cval["format"],$ts);
								}
							}
						}
					}
				}
				$this->addEntry($item);
				$this->startRow++;
				if ($this->startRow>=$this->highestRow) {
					$this->finalizeTable();
					$this->finished = true;
					break;
				}
			}
		} catch (Throwable $e) {
			$this->setError($e->getMessage());
		}
	}

	/**
	 * Feldlängen setzen
	 *
	 * @since version
	 */
	private function finalizeTable() {
		$db = JFactory::getDbo();
		foreach ($this->columns as $column => $cval)
		{
			if ($cval['type']=="text" && $cval['size']<=180) {
				$ns = (int) ($cval['size']/3*5);
				$db->setQuery("ALTER TABLE `".$this->tablename."` CHANGE `".$cval['title']."` `".$cval['title']."` VARCHAR(".$ns.")")->execute();
			}
		}
	}

	/**
	 * Prepare session for batch
	 *
	 * @since version
	 */
	public function xportToSession() {
		$session = JFactory::getSession();
		$data = array();
		$data["columns"] = $this->columns;
		$data["has_column_names"] = $this->has_column_names;
		$data["enclosure"] = $this->enclosure;
		$data["delimeter"] = $this->delimeter;
		$data["file"] = $this->file;
		$data["format"] = $this->format;
		$data["tablename"] = $this->tablename;
		$data["highestRow"] = $this->highestRow;
		$data["startRow"] = $this->startRow;
		$session->set('importdata', @json_encode($data));
	}

}

class ChunkReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
	private $startRow = 0;
	private $endRow   = 0;

	/**  Set the list of rows that we want to read  */
	public function setRows($startRow, $chunkSize) {
		$this->startRow = $startRow;
		$this->endRow   = $startRow + $chunkSize;
	}

	public function readCell($column, $row, $worksheetName = '') {
		//  Only read the heading row, and the configured rows
		if (($row == 1) || ($row >= $this->startRow && $row < $this->endRow)) {
			return true;
		}
		return false;
	}
}

