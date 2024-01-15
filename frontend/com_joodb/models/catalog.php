<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.model');

/**
 * JooDatabase Component Catalog Model
 */
class JoodbModelCatalog extends JModelLegacy
{
	/**
	 * Frontpage data array
	 *
	 * @var array
	 */
	var $_data = null;

	/**
	 * Frontpage total
	 *
	 * @var integer
	 */
	var $_total = null;

	/**
	 * Database Object
	 *
	 * @var object
	 */
	var $_joobase = null;

	/**
	 * Where Statemant
	 *
	 * @var string
	 */
	var $_where = null;

	/**
	 * Postion of current element
	 *
	 * @var integer
	 */
	var $_postion = null;

	/**
	 * Current query Statemant
	 *
	 * @var object
	 */
	var $_query = null;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$app = JFactory::getApplication();
		$params	= $app->getParams();
		$joodbId = $params->get("joobase",1);

		$reset = $app->input->get('reset');

		if (empty($joodbId)) $joodbId = $app->input->getInt('jbid', 1);
		$option = "com_joodb.".$joodbId;

		if ($menu = $app->getMenu()->getActive()) {
			$option .= "_".$menu->alias;
		}

		// Load the Database entry
		$this->_joobase =  JTable::getInstance( 'joodb', 'Table' );
		if (!$this->_joobase->load( $joodbId)) throw new RuntimeException(  $this->_joobase->getError(), 500);
		if ($this->_joobase->published==0) throw new RuntimeException( JText::sprintf( 'Database is unpublished or not availiable'),404);

		// access allowed... redirect to login if not
		JoodbHelper::checkAuthorization($this->_joobase,"accessd");
		$this->_db = $this->_joobase->getTableDBO();
		$this->setDatabase($this->_db);

		// get the table field list
		$this->_joobase->fields = $this->_db->getTableColumns($this->_joobase->table);

		$orderby = $app->getUserStateFromRequest($option.'.orderby', 'orderby',null, 'string');
		$ordering = $app->getUserStateFromRequest($option.'.ordering', 'ordering',null, 'cmd');

		// Get the pagination request variables
		$this->setState('limit', $app->getUserStateFromRequest($option.'.limit', 'limit', $params->get('limit','10'), 'int'));
		$this->setState('limitstart', $app->getUserStateFromRequest($option.'.limitstart', 'limitstart', 0,'int'));

		if ($reset=='true') {
			$this->setState('limit', $params->get('limit', '10'));
			$this->setState('limitstart', 0);
			$orderby = $params->get('orderby', 'fid');
			$ordering = $params->get('ordering', 'DESC');
			$app->setUserState($option.'.search','');
			$app->setUserState($option.'.searchfield','');
			$app->setUserState($option.'.alpha','');
		}

		if (empty($orderby)) $orderby = $params->get('orderby','fid');
		if (empty($ordering)) $ordering = $params->get('ordering','DESC');

		$this->setState('orderby', $this->_db->escape($orderby));
		$this->setState('ordering', $ordering);

		// Get search pramaters
		$search = $app->getUserStateFromRequest($option.'.search', 'search',null, 'string');
		if (empty($search) || $search==JText::_('search...')) $search = "";
		$search = $this->_db->escape(substr($search,0,40));
		$this->setState('search',$search);

		$where = array();
		$prefilter= $params->get("where_statement");
		if (!empty($prefilter)) {
			$where[] = ' ('.$prefilter.')';
		}

		$aold =  $app->getUserState($option.'.alpha',null);
		$alpha = $app->getUserStateFromRequest($option.'.alpha', 'alpha',null, 'string');
		if ($alpha=="*") $alpha ="";
		$this->setState('alphachar', $alpha);
		//build search string
		if (!empty($alpha) ) {
			if ($aold!=$alpha) {
				$this->setState('orderby', 'ftitle');
				$this->setState('ordering', 'ASC');
				$this->setState('limitstart', 0);
			}
			$where[] .= " ( a.`".$this->_joobase->ftitle."` LIKE '".$this->_db->escape($alpha)."%' )";
		}

		if (!empty($search) && strlen($search)>=2) {
			$sfield = $app->getUserStateFromRequest($option.'.searchfield', 'searchfield',null, 'string');
			if (!empty($sfield)) {
				$where[] = " ( a.`".addslashes($this->_db->escape($sfield))."` LIKE '%".$search."%' ) ";
			} else {
				if ($params->get('search_all',1)==1) {
					$wa = array();
					foreach ($this->_joobase->fields AS $var => $field) {
						switch ($field) {
							case 'varchar' : case 'char' : case 'tinytext' : case 'text' : case 'mediumtext' : case 'longtext' :
							$wa[] = "a.`".$var."` LIKE '%".$search."%'";
							break;
							case 'int' : case 'smallint' : case 'mediumint' : case 'bigint' : case 'tinyint' :
							if (is_numeric($search)) {
								$wa[] = "a.`".$var."` = '".(int) $search."'";
							}
							break;
							case 'date' : case 'datetime' : case 'timestamp' : case 'decimal' :
							$wa[] = "a.`".$var."` LIKE '".$search."%'";
							break;
							case 'tinyblob' : case 'mediumblob' : case 'blob' : case 'longblob' :
							break;
							default :
								$wa[] = "a.`".$var."` LIKE '".$search."'";
						}
					}
					$where[] = " ( ".join(" OR ", $wa)." ) ";
				} else {
					$words = explode(' ', $search);
					$wheres = array();

					foreach ($words as $word)
					{
						$word = $this->_db->quote('%' . $this->_db->escape($word, true) . '%', false);
						$wheres2 = array();
						$wheres2[] = 'a.`'.$this->_joobase->ftitle.'` LIKE ' . $word;
						$wheres2[] = 'a.`'.$this->_joobase->fcontent.'` LIKE ' . $word;
						if (!empty($this->_joobase->fabstract)) {
							$wheres2[] = 'a.`'.$this->_joobase->fabstract.'` LIKE ' . $word;
						}
						$wheres[] = implode(' OR ', $wheres2);
					}

					$where[] =  '(' . implode(') OR (', $wheres) . ')';
				}
			}
		}
		if (!empty($this->_joobase->fstate)) $where[] = "a.`".$this->_joobase->fstate."`='1'";

		$show_data = (($app->input->get('start',null)==null || $reset=="true") && $alpha==null && $params->get('form_only')==1) ? false : true;
		$this->setState('show_data',$show_data);

		// reduce result to selected items
		$ids = $app->getUserStateFromRequest($option.'.cid', 'cid',array(), 'array');
		if (is_array($ids) && count($ids)>=1) {
			foreach ($ids as $n => $fid)
				$ids[$n] = "a.`".$this->_joobase->fid."`= '".$fid."'";
			$where[] = " (".join(" OR ", $ids).") ";
		} else {
			$app->setUserState($option.'.cid',array());
		}

		// limit to user id
		if ($params->get('limit_to_user','0')==1 && $this->_joobase->getSubdata('fuser')) {
			$where[]  = "`".$this->_joobase->getSubdata('fuser')."`='".JFactory::getUser()->id."'";
		}

		// add filter from parametric search selects
		$gs =  $app->getUserStateFromRequest($option.'.gs', 'gs',array(), 'array');
		if (is_array($gs) && count($gs)>=1) {
			foreach ($gs as $column => $sv) {
				if (isset($this->_joobase->fields[$column])) {
					foreach ($sv as $n => $value)
						if (empty($value)) unset($sv[$n]);
						else $sv[$n] = "a.`".$column."` LIKE ".$this->_db->quote($value);
					if (count($sv)>=1) $where[] = " (".join(" OR ", $sv).") ";
				}
			}
		} else {
			$app->setUserState($option.'.gs',array());
		}

		// add field specific search with conditions
		$fs =  $app->getUserStateFromRequest($option.'.fs', 'fs',array(), 'array');
		if (is_array($fs) && count($fs)>=1) {
			foreach ($fs as $column => $sv) {
				if (isset($this->_joobase->fields[$column]) && is_array($sv)) {
					foreach ($sv AS $cond => $value) {
						if (!empty($value)) {
							$cond = strtolower($cond);
							if ($cond=="inset") {
								$where[] = " FIND_IN_SET(".$this->_db->quote($value)." ,a.`" . $column . "`)";
							} else {
								$conditions = array("like" => "LIKE", "exact" => "LIKE", "min" => ">=", "max" => "<=", "start" => "LIKE", "end" => "LIKE");
								$masks = array("like" => "#%s#", "exact" => "%s", "min" => "%s", "max" => "%s", "start" => "%s#", "end" => "#%s");
								$sc = $conditions[$cond];
								$value = sprintf($masks[$cond], $value);
								$where[] = " a.`" . $column . "` " . $sc . " " . $this->_db->quote(str_replace("#", "%", $value));
							}
						}
					}
				}
			}
		} else {
			$app->setUserState($option.'.fs',array());
		}

		// notepad view select marked articles
		if ($app->input->get("layout")=="notepad") {
			$where = array();
			$session = JFactory::getSession();
			$articles = preg_split("/:/",$session->get('articles'));
			if (count($articles)>=1) {
				foreach ($articles as $n => $article) $articles[$n] = " a.`".$this->_joobase->fid."`='".$article."' ";
			} else $articles = array(" a.`".$this->_joobase->fid."`='0'");
			$where[] = " (".join(" OR ", $articles).") ";
		}
		if (count($where)>=1) $this->_where = " WHERE ".join(" AND ", $where);
	}

	/**
	 * Get Object from JooDB table
	 *
	 * @access public
	 * @return single object
	 */
	public function getJoobase()
	{
		return	$this->_joobase;
	}

	/**
	 * Method to get Data from table in Database
	 *
	 * @access public
	 * @param boolean $export - ignore pagination limit and page
	 * @return array
	 */
	public function getData($export=false)
	{
		if ($this->getState('show_data')==false) return false;

		// Lets load the content if it doesn't already exist
		if (empty($this->_data)) {
			$query = $this->_buildQuery();
			$app = JFactory::getApplication();

			$pagination = $this->getPagination();
			if ($export===true) {
				$params	= $app->getParams();
				$pagination->limitstart = 0;
				$pagination->limit = $params->get('exportlimit',"100");
			}
			try {
				$this->_data = $this->_getList($query,$pagination->limitstart,$pagination->limit);
			} catch (Throwable $e) {
				$this->setError($e->getMessage());
			}
		}
		return $this->_data;
	}


	/**
	 * Return Export items ...
	 */
	public function getExport() {
		return $this->getData(true);
	}

	/**
	 * Method to get the total number of items in the Database
	 *
	 * @access public
	 * @return integer
	 */
	function getTotal()
	{
		if ($this->getState('show_data')==false) return false;
		// Get total if not exits
		if (empty($this->_total))
		{
			$query = 'SELECT `'.$this->_joobase->fid.'` AS numlinks FROM `'.$this->_joobase->table."` AS a ".$this->_where;
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}

	/**
	 * Get the possible values from a column regarding the current selection
	 *
	 * @access public
	 * @return values
	 */
	public function getColumnVals($column,$use_search=true)
	{
		// Get total if not exits
		if ($use_search && !empty($this->_data)) {
			$cw = $this->_where;
		} else {
			$app = JFactory::getApplication();
			$params	= $app->getParams();
			$cw = $params->get("where_statement");
			if (!empty($cw)) $cw = "WHERE ".$cw;
		}
		$fields = $this->_joobase->getTableFieldList();
		$type = preg_split("/\(/",$fields[$column]);
		$split = ($type[0]=="enum" || $type[0]=="set") ? true : false;
		$query = "SELECT count(distinct(`".$this->_joobase->fid."`)) AS count,a.`".$column."` AS value, '' AS delimeter FROM `"
			.$this->_joobase->table."` AS a ".$cw." GROUP BY a.`".$column."` ORDER BY a.`".$column."` ASC";
		$values = $this->_getList($query);
		if (!empty($values)) {
			foreach ($values as $value) {
				if (!empty($value->value) && substr_count($value->value,",")>=1) { // its a value list - rebuild values
					$cw .= (empty($cw)) ? " WHERE a.`".$column."` IS NOT NULL" : " AND a.`".$column."` IS NOT NULL";
					$this->_db->setQuery("SELECT a.`".$column."` FROM `".$this->_joobase->table."` AS a ".$cw." ORDER BY a.`".$column."` ASC");
					$values = $this->_db->loadColumn();
					$v= array();
					foreach ($values as $value) {
						if ($split===true) {
							$parts = preg_split("/,/",$value);
							foreach ($parts as $p) $v[] = trim($p);
						} else $v[] = $value;
					}
					sort($v);
					$c = array_count_values($v);
					$values = array();
					foreach ($c as $value => $count) {
						$values[] = (object) array ("value" => $value, 'count' => $count, "delimeter" => '%');
					}
					break;
				}
			}
		}
		return $values;
	}


	/**
	 * Method to get a pagination object
	 *
	 * @access public
	 * @return integer
	 */
	public function getPagination()
	{
		if (empty($this->_pagination))
		{
			jimport('joomla.html.pagination');
			$this->_pagination = new JPagination( $this->getTotal(), $this->getState('limitstart'), $this->getState('limit') );
		}
		return $this->_pagination;
	}

	/**
	 * Method to get a search value
	 *
	 * @access public
	 * @return string
	 */
	public function getSearch()
	{
		return $this->getState('search');
	}

	/**
	 * Method to get a search value
	 *
	 * @access public
	 * @return string
	 */
	public function getAlphachar()
	{
		return $this->getState('alphachar');
	}


	/**
	 * Try to get the id of the next or Previous entry in katalog
	 *
	 * @param string $dir (next or prev)
	 * @return $item with position AND total
	 */
	public function getSideElementUrl($dir="next",$position=0) {
		$input  = JFactory::getApplication()->input;

		if (empty($this->_position) && empty($this->_total)) {
			$this->_position = $input->getInt('position');
			$this->_total = $input->getInt('total');
		}

		if ( $this->getState('orderby') == "random") $this->setState('orderby','fid');
		$orderby = $this->getState('orderby');
		if (!isset($this->_joobase->fields[$orderby]) && isset($this->_joobase->{$orderby})) $orderby = $this->_joobase->{$orderby};
		$orderby = " ORDER BY a.`".$orderby."` ".$this->getState('ordering');
		// get position only once to prevent heavy mysql load
		if (empty($this->_position)) {
			$id = $input->getInt('id', 1);
			$query = "SELECT p.`jb_pos` FROM "
				."(SELECT a.`".$this->_joobase->fid."`, @rownum := @rownum +1 AS jb_pos FROM "
				."`".$this->_joobase->table."` a JOIN (SELECT @rownum :=0) r "
				.$this->_where." GROUP BY a.`".$this->_joobase->fid."`"
				.$orderby.") p WHERE p.`".$this->_joobase->fid."` = ".$id;
			$this->_db->setQuery($query,0,1);
			$this->_position = $this->_db->loadResult();
		}
		// get start postion in result
		$start_pos = ($dir=="next") ? $this->_position+1 : $this->_position-1;
		if ($start_pos <= 0) $start_pos = $this->_total;
		if ($start_pos > $this->getTotal()) $start_pos = 1;

		$this->_db->setQuery($this->_buildQuery(),($start_pos-1),1);
		if ($item = $this->_db->loadObject()) {
			$item->jb_total = $this->getTotal();
			$item->jb_pos = $start_pos;
		}

		return $item;
	}

	/**
	 * Build query string
	 *
	 * @access non-public
	 * @return string
	 */
	protected function _buildQuery() {
		if (empty($this->_query)) {

			// Get only the length of binary fields
			$select = array();
			foreach ($this->_joobase->fields AS $var => $field) {
				if (strpos($field,"blob")===false) {
					$select[] = "a.`".$var."`";
				} else {
					$select[] = "OCTET_LENGTH(a.`".$var."`) AS `".$var."`";
				}
			}

			/* Query table and return the relevant fields. */
			$this->_query = "SELECT ".join(",",$select)
				. " FROM `" . $this->_joobase->table . "` AS a"
				. $this->_where
				. " GROUP BY a.`" . $this->_joobase->fid . "`";

			// build ordering
			$orderby = $this->getState('orderby');
			if ($this->getState('orderby') == "random") {
				$this->_query .= " ORDER BY RAND() ";
			} else {
				if (!isset($this->_joobase->fields[$orderby]) && isset($this->_joobase->{$orderby})) $orderby = $this->_joobase->{$orderby};
				$this->_query .= " ORDER BY a.`" . $orderby . "` " . $this->getState('ordering');
			}
		}
		// echo (string) $this->_query;
		return $this->_query;
	}

}
