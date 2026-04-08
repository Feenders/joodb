<?php
/**
* @file listdata.php
* @package		Joomdb
* @author	feenders - dirk hoeschen (hoeschen@feenders.de)
* @abstract	custom component for client
* @version  4.0
*/

namespace Feenders\Component\Joodb\Administrator\Model;

defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseQuery;
use function defined;

/**
 * Model to list all Items
 */
class ListdataModel extends ListModel {

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
		$app = Factory::getApplication();
		$jbid = $app->getUserStateFromRequest($this->context.'.list.jbid', 'joodbid');
		$this->setState('list.jbid', $jbid);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '*', 'string');
		$this->setState('filter.published', $published);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_joodb');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('name', 'ASC');

	}

	/**
	 * Get Joobd Object
	 * @return bool|Table
	 *
	 */
	public function getJb() {
		if (empty($this->jb)) {
			$db = $this->getDatabase();
			$this->jb = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
			$this->jb->load($this->state->get('list.jbid', 1) );
		}
		return $this->jb;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	DatabaseQuery
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

