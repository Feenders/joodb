<?php
/**
 * JooDB Table Definition
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/**  */
class TableJoodb extends JTable
{

	/** @var int Primary key */
	var $id					= null;
	/** @var string */
	var $name				= null;
	/** @var string */
	var $table				= null;
	/** @var text */
	var $tpl_list		= null;
	/** @var text */
	var $tpl_single		= null;
	/** @var text */
	var $tpl_print			= null;
	/** @var text */
	var $tpl_form			= null;
	/** @var string */
	var $fid				= null;
	/** @var string */
	var $ftitle				= null;
	/** @var string */
	var $fcontent			= null;
	/** @var string */
	var $fabstract			= null;
	/** @var string */
	var $fdate				= null;
	/** @var string */
	var $fstate			    = null;
	/** @var boolean */
	var $published				= 1;
	/** @var string */
	var $params				=  null;
	/** @var date */
	var $created				= null;

	/** Database Object of the Data Table database
	 * @var		object
	 * @since	1.7
	 */
	protected $_tbldb = null;

	/** Parameter Object of the Data Table database
	 * @var		object
	 * @since	1.7
	 */
	protected $_jbparams = null;

	/** Array with Links & Subtemplates
	 * @var		object
	 * @since	2.0
	 */
	protected $_subitems = array();

	/** Array with related subitems for forms
	 * @var		object
	 * @since	3.0
	 */
	protected $_subfields = array();

	/**
	 *  Flexible objact array with additional data
	 */
	protected $_subdata = array();
	protected $_sdata_fields = array("fuser","falias");

	/**
	 * @param database A database connector object
	 */
	public function __construct( &$db ) {
		parent::__construct( '#__joodb', 'id', $db );
		$this->_tbldb = $this->getDbo();
	}

	/**
	 * Bind input
	 * @see JTable::bind()
	 */
	public function bind($src,$ignore=array()) {
		$input = JFactory::getApplication()->input;
		$extserv = $input->getString("server");
		// New enty with external DB
		if (!empty($extserv)) {
			$params = array("extdb_server"=>$extserv,"extdb_user"=>$input->getString('user'),"extdb_pass"=>$input->getString('pass'),"extdb"=>$input->getString('database'));
		} else {
			$params = $input->post->get( 'params', array(), 'array' );
		}

		// get parameters and store em as array
		$result = parent::bind($src,$ignore);
		if (!empty($params))  $this->params  = json_encode($params);
		// Extract parameters from json value
		$this->_jbparams = new JRegistry($this->params);
		$p = & $this->_jbparams;
		// Prepare external Database for Datatable
		if ($p->get('extdb_server')!="") {
			$options = array ('host' => $p->get('extdb_server'), 'user' => $p->get('extdb_user'), 'password' => $p->get('extdb_pass'), 'database' => $p->get('extdb'),'prefix' => '');
			try {
				$this->_tbldb = JDatabaseDriver::getInstance($options);
			} catch (\Exception $e) {
				$this->setError('Database Error: ' .$e->getMessage());
			}
		}

		return $result;
	}

	/**
	 * Overloaded check function
	 */
	public function check()
	{

		if(empty($this->name)) {
			$this->setError(JText::_('Database must have a name'));
			return false;
		}
		if(empty($this->table)) {
			$this->setError(JText::_('Please choose Table'));
			return false;
		}
		if(empty($this->fid)) {
			$this->setError(JText::_('Error Define Fields'));
			return false;
		}
		if(empty($this->ftitle)) {
			$this->setError(JText::_('Error Define Fields'));
			return false;
		}
		if(empty($this->fcontent)) {
			$this->setError(JText::_('Error Define Fields'));
			return false;
		}
		/** load the default templates into field if empty */
		if(empty($this->tpl_list)) {
			$this->tpl_list = $this->getDefaultTemplate("listview");
		}
		if(empty($this->tpl_single)) {
			$this->tpl_single = $this->getDefaultTemplate("singleview");
		}
		if(empty($this->tpl_print)) {
			$this->tpl_print = $this->getDefaultTemplate("printview");
		}
		if(empty($this->tpl_form)) {
			$this->tpl_form = $this->getDefaultTemplate("formview");
		}
		return true;
	}


	/**
	 * Overload store Function
	 */
	public function store($updateNulls = false) {
		$app = JFactory::getApplication();
		if (parent::store($updateNulls)) {
			// store additional data
			foreach ($this->_sdata_fields as $field) {
				$this->_db->setQuery("DELETE FROM `#__joodb_settings` WHERE "
				                     ." `name` = '".$field."' AND `jb_id` =".$this->id);
				$this->_db->execute();
				if (!empty($app->input->getString($field))) {
					$fo = new JObject();
					$fo->jb_id=$this->id;
					$fo->name=$field;
					$fo->value=$app->input->getString($field);
					$this->_db->insertObject('#__joodb_settings',$fo);
				}
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Overloaded load function
	 */
	public function load($id=null,$reset=false)
	{
		return parent::load($id,$reset);
	}


	/**
	 * load the default templates into field
	 */
	public function getDefaultTemplate($tname) {
		$tfile = JPATH_COMPONENT_ADMINISTRATOR.'/assets/'.$tname.".tpl";
		$db	= $this->getTableDBO();
		if (file_exists($tfile)) {
			$template = file_get_contents($tfile);
			// replace tag with table specific infos
			if ($tname=="listview") {
				$header = JText::_('Title').'<span style="float:right">'.JText::_('ORDER BY')
				          .' {joodb orderlink|'.$this->fid.'|'.ucfirst($this->fid).'} {joodb orderlink|'.$this->ftitle.'|'.ucfirst($this->ftitle).'}</span>';
				$loop = "<div class='tr {joodb loopclass}' ><div class='td' style='width:80px;text-align: center; '><strong>{joodb ".$this->fid."}</strong></div>";
				$loop .= "<div class='td'><h3 class='title'>{joodb ".$this->ftitle."}</h3>".chr(10);
				if (!empty($this->fdate)) {
					$loop .= "<span class='small'>".JText::_('Date').": {joodb ".$this->fdate."}</span>";
				}
				if (!empty($this->fabstract)) {
					$loop .= "<p>{joodb ".$this->fabstract."|120}</p>".chr(10);
				} else {
					$loop .= "<p>{joodb ".$this->fcontent."|120}</p>".chr(10);
				}
				$loop .= '<div class="readon">{joodb readon}</div></div></div>'.chr(10);
				$template = str_replace("#C_DEFAULT_HEADER", $header, $template);
				$template = str_replace("#C_DEFAULT_LOOP", $loop, $template);
			} else if ($tname=="formview") {
				$fields = $db->getTableColumns($this->table);
				if (!isset($this->fuser)) $this->fuser=null;
				$content = "<dl>".chr(10);
				foreach ($fields as $fname => $ftype) {
					if (($fname!=$this->fid) && ($fname!=$this->fstate) && ($fname!=$this->fuser)) {
						$content .= "<dt><label for=\"jform_".preg_replace("/[^A-Z0-9]/i","",$fname)."\">".ucfirst(str_replace("_"," ",$fname))."</label></dt>".chr(10);
						$content .= "<dd>{joodb form|".$fname."}</dd>".chr(10);
					}
				}
				$content .= "</dl>";
				$template = str_replace("#S_DEFAULT_FIELDS", $content, $template);
			} else {
				$template = str_replace("#S_DEFAULT_TITLE","{joodb field|".$this->ftitle."}",$template);
				$content =  (!empty($this->fdate)) ? JText::_('Date').": {joodb field|".$this->fdate."}" : "";
				$template = str_replace("#S_DEFAULT_DATE",$content,$template);
				$template = str_replace("#S_DEFAULT_CONTENT","{joodb field|".$this->fcontent."}",$template);
				$fields = $db->getTableColumns($this->table);
				$content = "<dl>".chr(10);
				foreach ($fields as $fname => $ftype) {
					if (($fname!=$this->ftitle) && ($fname!=$this->fcontent) && ($fname!=$this->fabstract)) {
						$content .= "<dt>".ucfirst($fname).":</dt>".chr(10);
						$content .= "<dd>{joodb field|".$fname."}</dd>".chr(10);
					}
				}
				$content .= "</dl>";
				$template = str_replace("#S_DEFAULT_FIELDS", $content, $template);
			}
		}
		return $template;
	}


	/**
	 * Get the external database from the joodb parameters
	 * return database object
	 */
	public function &getTableDBO() {
		return $this->_tbldb;
	}

	/**
	 * Get the parameters object for the joobase table
	 * return parameters object
	 */
	public function &getParameters() {
		return $this->_jbparams;
	}

	/**
	 * Get the linked tables and subtemplates
	 * @params string label
	 * return subitems array or otional value of specific entry
	 */
	public function &getSubitems($label=null) {
		// load subforms
		if (empty($this->_subitems)) {
			$this->_db->setQuery("SELECT id, value FROM `#__joodb_settings` WHERE "
			                     ." `name` = 'subitem' AND `jb_id` =".$this->id." ORDER BY id asc");
			if ($data = $this->_db->loadObjectList()) {
				foreach ($data as $v) {
					$s = json_decode($v->value);
					$this->_subitems[$s->label] = $s;
					$this->_subitems[$s->label]->id = $v->id;
				}
			}
		}
		if (isset($label)) {
			if (!isset($this->_subitems[$label])) $this->_subitems[$label]=false;
			return $this->_subitems[$label];
		} else
			return $this->_subitems;
	}


	/**
	 * Get element from list regarding the type of relation
	 *
	 * @param $field_name
	 * @return bool or subfom element
	 */
	public function getSubFormField($field_name) {
		if (empty($this->_subfields)) {
			$subitems = $this->getSubitems();
			// create a set of fields to replace
			foreach ($subitems AS $subitem)
				if ($subitem->type == "4")
					if (!isset($this->_subfields[$subitem->idx_sub]))
						$this->_subfields[$subitem->idx_sub] = $subitem;
		}
		return (isset($this->_subfields[$field_name])) ? $this->_subfields[$field_name] : false;
	}

	/**
	 * Get a single subitem
	 */
	public function &getSubitem($label) {
		return $this->getSubitems($label);
	}


	/**
	 * Get additional data from the setings table
	 * @params string label
	 * return subitems array or otional value of specific entry
	 */
	public function &getSubdata($label=null) {
		// load subforms
		if (empty($this->_subdata)) {
			$this->_db->setQuery("SELECT name, value FROM `#__joodb_settings` WHERE "
			                     ." `name` != 'subitem' AND `jb_id` =".$this->id);
			$this->_subdata = $this->_db->loadAssocList('name','value');
		}
		if (!empty($label)) {
			if (!isset($this->_subdata[$label])) $this->_subdata[$label] = false;
			return $this->_subdata[$label];
		} else {
			return $this->_subdata;
		}
	}

	/**
	 * Get the field List from the Database table;
	 * @param null $table string table name!
	 * @return array
	 */
	public function getTableFieldList($table=null) {
		if (empty($table)) $table = $this->table;
		$this->_tbldb->connect();
		$result = array();
		// Set the query to get the table fields statement.
		$this->_tbldb->setQuery("SHOW FULL COLUMNS FROM `". $this->_tbldb->escape($table)."`");
		try {
			$fields = $this->_tbldb->loadObjectList();
			foreach ($fields as $field){
				$result[$field->Field] = $field->Type;
			}
		} catch (RuntimeException $e) { }
		return $result;
	}

}
