<?php
/**
* @file listdata.php
* @package		Joomdb
* @author	feenders - dirk hoeschen (hoeschen@feenders.de)
* @abstract	custom component for client
* @version  4.0
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

class joodbModelListdata extends JModelList {

	protected $jb = null;

	/**
	 * Constructor.
	 *
	 * @param    array    An optional associative array of configuration settings.
	 * @see        JController
	 * @since    1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'title', 'created', 'published'
			);
		}

		parent::__construct($config);
	}


	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();
		$jbid = $app->getUserStateFromRequest($this->context.'.list.jbid', 'joodbid');
		$this->setState('list.jbid', $jbid);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '*', 'string');
		$this->setState('filter.published', $published);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_joodb');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('name', 'ASC');

	}

	/**
	 * Get Joobd Object
	 * @return bool|\Joomla\CMS\Table\Table
	 *
	 * @since version
	 */
	public function &getJb() {
		if (empty($this->jb)) {
			$this->jb = JTable::getInstance( 'joodb', 'Table' );
			$this->jb->load($this->state->get('list.jbid', 1) );
		}
		return $this->jb;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 * @since	1.6
	 */
	protected function getListQuery()
	{

		// Initialize variables
		$jb = $this->getJb();
		$this->_db	= $jb->getTableDBO();
		$this->setDatabase($this->_db);
		$db = & $this->_db;
		$query	= $this->_db->getQuery(true)
			->select('*')
			->from("`".$jb->table."`");

		// Filter by search in title
		$search = $this->getState('filter.search');
		// Keyword filter
		if (!empty($search)) {
			if (is_numeric($search)) {
				$query->where("`".$jb->fid."`=".(int)$search);
			} else {
				$query->where("`".$jb->ftitle."` LIKE ".$db->Quote( '%'.$db->escape( $search, true ).'%', false )
					." OR `".$jb->fid."`=".$db->quote($search));
			}
		}

		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');

		$jb = $this->getJb();

		$fields = array();
		$fields['id'] = $jb->fid;
		$fields['title'] = $jb->ftitle;
		$fields['published'] = $jb->fstate;
		$fields['created'] = $jb->fdate;

		if (isset($fields[$orderCol]) && !empty($fields[$orderCol])) {
			$orderCol = $fields[$orderCol];
		} else {
			$orderCol = $fields['title'];
			$orderDirn = "ASC";
		}

		if ($orderCol && $orderDirn) {
			$query->order($db->escape($orderCol.' '.$orderDirn));
		}

		return $query;

	}

}

