<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Component Helper
jimport('joomla.application.component.helper');

/**
 * JooDB Component Helper
 */
class SubtemplateHelper extends JoodbHelper
{
	
	/**
	 * Get subtemplate content ...
	 * @param string $name
	 * @param object $joobase
	 * @param object $item
	 */
	static function getSubTemplate($name, &$joobase,&$item) {
		$return = "";
		if ($stmpl = $joobase->getSubitem($name)) {
			$db = $joobase->getTableDBO();
			$query	= "SELECT a.* FROM `".$stmpl->table."` AS a ";
			switch ($stmpl->type) {
				case '1' :
					$query .= " WHERE a.`".$stmpl->id_field."`='".$item->{$joobase->fid}."' ";
				break;	
				case '2' :
					$query .= " LEFT JOIN `".$stmpl->idx_table."` AS c ON c.`".$stmpl->idx_id2."`=a.`".$stmpl->id_field."` ";
					$query .= " WHERE c.`".$stmpl->idx_id1."`='".$item->{$joobase->fid}."' ";
				break;	
				case '3' :
					$query .= " WHERE a.`".$stmpl->id_field."`='".$item->{$joobase->fid}."' ";
				break;	
				case '4' :			
					$query .= " WHERE a.`".$stmpl->id_field."`='".$item->{$stmpl->idx_sub}."' ";
				break;					
			}
			$query .= " ORDER BY a.`".$stmpl->name_field."` ASC ";
			$db->setquery($query);
			if ($subdata=$db->loadObjectList()) {
				// replace fieldtypes with fields in subtable
				$subbase = clone $joobase;
				$subbase->fields = $subbase->getTableFieldList($stmpl->table);
				foreach ($subdata as $n => $subitem) {
					$subitem->loopclass = ($n % 2 == 0) ? "odd" : "even";
					$parts = JoodbHelper::splitTemplate($stmpl->content);
					$return .= self::parseTemplate($subbase, $parts, $subitem);
				}
			}
		}
		return $return;
	}
	
	/**
 	* Parse template for wildcards and return text
 	*
 	* @access public
 	* @param JooDB-Objext with fieldnames, Array with template parts, Object with Item-Data
 	* @return The parsed output
 	*
 	*/
	static function parseTemplate(&$joobase, &$parts, &$item,&$params = null) {
		$output = "";
		$level = 0;
		self::$outputStates = array(true);
	   	// replace item content with wildcards
    	foreach( $parts as $part ) {
		    $doOutput = self::GetOutputState($item, $part, $level);
			if ($doOutput) {
				// replace field command with 1st parameter
				if ($part->function == "field") {
					$part->function = $part->parameter[0];
					array_shift($part->parameter);
					$output .= parent::replaceField($joobase, $part, $item->{$part->function});
				} else if (isset($joobase->fields[$part->function])) {
					$output .= parent::replaceField($joobase, $part, $item->{$part->function});
				} else if ($part->function == "subtemplate") { // parse subtemplate name
					$output .= self::getSubTemplate($part->parameter[0], $joobase, $item);
				} else if ($part->function == "loopclass") {
					$output .= $item->loopclass;
				} else {
					$plugin = JPATH_COMPONENT . "/plugins/" . JFilterOutput::stringURLSafe($part->function) . ".php";
					if (file_exists($plugin)) include $plugin;
				}
				$output .= $part->text;
			}
  	 	}
  	 	return $output;
	}

	
}
