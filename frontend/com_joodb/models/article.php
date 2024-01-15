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
 * JooDatabase Component single item Model
 */
class JoodbModelArticle extends JModelLegacy
{
	/**
	 * Entry Item Object
	 *
	 * @var array
	 */
	var $_data = null;

	/**
	 * Database Object
	 *
	 * @var object
	 */
	var $_joobase = null;

	/**
	 * Constructor
	 */
    public function __construct($config=array())
	{
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
            );
        };
        parent::__construct($config);
		$app = JFactory::getApplication();
		$params	= $app->getParams();
		$joobase = $params->get("joobase",0);
		// Load the Database parameters
		if ($joobase==0) $joobase = $app->input->getInt('joobase', 1);
		$this->_joobase = JTable::getInstance( 'joodb', 'Table' );
		if (!$this->_joobase->load( $joobase)) throw new RuntimeException(  $this->_joobase->getError(), 500);
		if ($this->_joobase->published==0) throw new RuntimeException( JText::sprintf( 'Database is unpublished or not availiable'),404);
		$this->_db = $this->_joobase->getTableDBO();

		// access allowed... redirect to login if not
		JoodbHelper::checkAuthorization($this->_joobase,"accessd");

		// get the table field list
		$this->_joobase->fields = $this->_joobase->getTableFieldList();

		$id = $params->get("id", 0);
		if (empty($id)) {
			$id = $app->input->get('id', null);
			if (empty($id)) {
				$falias=$this->_joobase->getSubdata('falias');
				$alias = $app->input->getString('alias', null);
				if (!empty($falias) && !empty($alias)) {
					$id = $this->_db->setQuery("SELECT `".$this->_joobase->fid."` FROM `".$this->_joobase->table."` WHERE `".$falias."`=".$this->_db->quote($alias))->loadResult();
				}
			}
		}

		$this->setId((int)$id);
	}

	/**
	 * Method to set the article id
	 *
	 * @access	public
	 * @param	int	Article ID number
	 */
	public function setId($id)
	{
		// Set new article ID and wipe data
		$this->_id		= $id;
		$this->_article	= null;
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
	 * @return array
	 */
	public function getData()
	{

		// Lets load the content if it doesn't already exist
		if (empty($this->_data))
		{
			$statequery = ($this->_joobase->fstate) ? " AND `".$this->_joobase->fstate."`=1 " : " ";
			/* Query single object. */
			$this->_db->setQuery('SELECT * FROM `'.$this->_joobase->table
							.'` WHERE `'.$this->_joobase->fid.'`='.$this->_id.$statequery.' LIMIT 1;');
			$this->_data = $this->_db->loadObject();
			if (empty($this->_data))	{
				throw new RuntimeException(JText::sprintf( 'Article # not found', $this->_id ),404 );
			}
		}

		return $this->_data;
	}

}
