<?php
/**
* @file joodb.php created 28.07.2011, 12:23:47
* @package		Joomdb
* @author	feenders - dirk hoeschen (hoeschen@feenders.de)
* @abstract	custom component for client
* @version  4.0
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

class joodbModelJoodb extends JModelList {

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
				'id', 'a.id',
				'name', 'a.name',
				'published', 'a.published',
				'created', 'a.created',
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
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '*', 'string');
		$this->setState('filter.published', $published);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_joodb');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.id', 'DESC');

	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('a.*');
		$query->from('`#__joodb` AS a');

		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('a.published = '.(int) $published);
		} else if ($published === '' || $published == '*') {
			$query->where('(a.published IN (0, 1))');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		// Keyword filter
		if (!empty($search)) {
			if (is_numeric($search)) {
				$query->where("`id`=".(int)$search);
			} else {
				$query->where("`name` LIKE ".$db->Quote( '%'.$db->escape( $search, true ).'%', false ));
			}
		}

		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');
		if ($orderCol && $orderDirn) {
			$query->order($db->escape($orderCol.' '.$orderDirn));
		}

		return $query;

	}

}

