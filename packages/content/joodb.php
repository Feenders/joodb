<?php
/**
*
* Plugin to display a single Database entry in a normal content article
*
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE
* @author		Dirk Hoeschen (hoeschen@feenders.de)
* @version 	    3.0
*
**/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgContentJoodb extends JPlugin {
	
	protected $_joobase = null;
	protected $_data = null;
	protected $_db = null;
	
	/**
	 * Constructor
	 *
	 * @access      protected
	 * @param       object  $subject The object to observe
	 * @param       array   $config  An array that holds the plugin configuration
	 * @since       1.5
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$t = $this->loadLanguage("com_joodb",JPATH_BASE);
	}

	/**
	 * Plugin that replaces {joodbitem XX} with a single view of the dataset with ID #XX
	 *
	 * @param	string	The context of the content being passed to the plugin.
	 * @param	mixed	An object with a "text" property or the string to be cloaked.
	 * @param	array	Additional parameters.
	 * @param	int		Optional page number. Unused. Defaults to zero.
	 * @return	boolean	True on success.
	 */
	public function onContentPrepare($context, &$row, &$params, $limitstart=0 ) {
		return $this->_getJoodbContent($row, $params);
	}

	/**
	 * Parse article content and replace finds ...
	 * 
	 * @param object $row
	 * @param object $params
	 */	
	protected function _getJoodbContent(&$row, &$params ) {
		// find tags in content-text
		preg_match_all('/\{joodbitem (.*?)\}/iU',$row->text, $matches);
		if (!empty($matches)) {

			/**
			 * todo: rather primitive check for pro version 
			 */
			if (!file_exists(JPATH_SITE.'/components/com_joodb/helpers/subtemplate.php')) return false;
			require_once(JPATH_SITE.'/components/com_joodb/models/article.php');
			require_once(JPATH_SITE.'/components/com_joodb/helpers/joodb.php');
			require_once(JPATH_SITE.'/components/com_joodb/helpers/subtemplate.php');

			JTable::addIncludePath(JPATH_SITE.'/administrator/components/com_joodb/tables');
			$this->_joobase = JTable::getInstance('joodb','Table');
			if (!$this->_getJoobase($this->params->get('joobase',1))) return false;
			$app = JFactory::getApplication('site');
			$cp = new JRegistry();
            $jp = JComponentHelper::getParams('com_joodb');
			$cp->merge($jp);
			$cp->set('link_titles','0');
			$cp->set('link_urls','0');

			JoodbHelper::checkAuthorization($this->_joobase,"accessd");
			
			foreach($matches[1] as $match) {
				// parameter 2 use joodb x
				$p = preg_split("/,/", $match);
				if (count($p)>=2) {
					$id = (int) $p[0];
					$this->_getJoobase((int) $p[1]);
				} else {
					$id = (int) $match;
				}

                $parts = JoodbHelper::splitTemplate($this->_joobase->tpl_single);
                // remove backbutton e.g.
                $unwanted = array("backbutton","nextbutton","prevbutton");
                foreach ($parts as &$p)
                    if (in_array($p->function,$unwanted))
                        $p->function="";
				
				if ($this->_getData($id)) {
					JoodbHelper::prepareDocument();
                    $output =  JoodbHelper::parseTemplate($this->_joobase, $parts,$this->_data,$cp);
				} else $output = JText::sprintf('ENTRY_NOT_FOUND',$id);
				$row->text = preg_replace("/\{joodbitem ".$match."\}/i",'<div class="joodb database-article">'.$output.'</div>', $row->text);
			}
		}		
		return true;
	}
	

	/**
	 * Method to get Data from table in Database
	 *
	 * @access protectes
	 * @return boolean true or false
	 */
	protected function _getData($id)
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_data))
		{
			$statequery = (!empty($this->_joobase->fstate)) ? " AND `".$this->_joobase->fstate."`=1 " : " ";
			$db = $this->_joobase->getTableDBO();
			/* Query single object. */
			$db->setQuery("SELECT * FROM `".$this->_joobase->table
							."` WHERE `".$this->_joobase->fid."`='".(int)$id."'".$statequery." LIMIT 1;");
			$this->_data = $db->loadObject();
		}
		return (empty($this->_data)) ? false : true;
	}	

	/**
	 * Prepare Joodatabase
	 *
	 * @access protectes
	 * @return boolean true or false
	 */
	protected function _getJoobase($id)
	{
		if (empty($this->_joobase->id) || $this->_joobase->id!=$id) {
			$this->_joobase->load($id);
            if (empty($this->_joobase) || empty($this->_joobase->id)) return false;
            $this->_db = $this->_joobase->getTableDBO();
			// get the table field list
            $this->_joobase->fields = $this->_db->getTableColumns($this->_joobase->table);
		}
		return (empty($this->_joobase)) ? false : true;
	}
	
}
