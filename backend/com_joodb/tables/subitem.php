<?php
/**
* @file subitem.php created 28.07.2011, 12:23:47
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

class TableSubItem extends JTable
{
	var $id = null;
	var $name = 'subitem';	
	var $value = '{"label":"","type":"","table":"","id_field":"","name_field":"","idx_table":"","idx_id1":"","idx_id2":"","idx_sub":"","content":""}';
	var $jb_id = null;	
	
	/**
	* @param database A database connector object
	*/
	function __construct( &$db ) {
		parent::__construct( '#__joodb_settings', 'id', $db );
	}	
	
	/**
	 * Overloaded check function
	 */
	function check()
	{
		if(empty($this->jb_id)) return false;
		$input  = JFactory::getApplication()->getInput();
		// bind data to object and output as json string
		$o = new JObject();
		$o->label = JFilterOutput::stringURLSafe($input->getString('label'));
		$o->type =  $input->getInt('type','1');
		$o->table =  $input->getString('table');
		$o->id_field =  $input->getString('id_field');
		$o->name_field =  $input->getString('name_field');		
		$o->idx_table =  $input->getString('idx_table');
		$o->idx_id1 =  $input->getString('idx_id1');
		$o->idx_id2 =  $input->getString('idx_id2');
		$o->idx_sub =  $input->getString('idx_sub');
		$o->content = $input->get("content", '','raw');
		$this->value = json_encode($o);		
		return  true;
		
	}	
	
	/**
	 * Overloaded load function
	 */
	function load($id=null,$reset=false)
	{
		parent::load($id,$reset);
		$this->value = json_decode($this->value);
		return ($this->id) ? true : false;
	}	
			
	
}


?>