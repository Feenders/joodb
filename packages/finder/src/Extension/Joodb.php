<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Finder.joodb
 *
 * @copyright   (C) 2024 computer * daten * netze : feenders. <https://www.feenders.de>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Finder\Joodb\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Smart Search adapter for Joomla Joodb.
 *
 * @since  2.5
 */
final class Joodb extends Adapter
{
	use DatabaseAwareTrait;

	/**
	 * The plugin identifier.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $context = 'Joodb';

	/**
	 * The extension name.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $extension = 'com_joodb';

	/**
	 * The sublayout to use when rendering the results.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $layout = 'joodb';

	/**
	 * The type of content that the adapter indexes.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $type_title = 'Entry';

	/**
	 * The table name.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $table = '';

	/**
	 * The field the published state is stored in.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $state_field = 'published';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * JooDB table
	 *
	 * @var null
	 * @since 5.0
	 */
	protected $jooDb = null;

	/**
	 * Method to remove the link information for items that have been deleted.
	 *
	 * @param   string  $context  The context of the action being performed.
	 * @param   Table   $table    A Table object containing the record to be deleted.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  \Exception on database error.
	 */
	public function onFinderAfterDelete($context, $table): void
	{
		if ($context === 'com_joodb.entry') {
			$id = $table->id;
		} elseif ($context === 'com_finder.index') {
			$id = $table->link_id;
		} else {
			return;
		}

		// Remove the item from the index.
		$this->remove($id);
	}

	/**
	 * Smart Search after save content method.
	 * Reindexes the link information for a newsfeed that has been saved.
	 * It also makes adjustments if the access level of a newsfeed item or
	 * the category to which it belongs has changed.
	 *
	 * @param   string   $context  The context of the content passed to the plugin.
	 * @param   Table    $row      A Table object.
	 * @param   boolean  $isNew    True if the content has just been created.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  \Exception on database error.
	 */
	public function onFinderAfterSave($context, $row, $isNew): void
	{
		// We only want to handle joodb here.
		if ($context === 'com_joodb.entry') {
			// Run the setup method.
			$this->setup();
			if ($row->id === $this->jooDb->id) {
				// Reindex the item.
				$this->reindex($row->item_id);
			}
		}
	}

	/**
	 * Method to update the link information for items that have been changed
	 * from outside the edit screen. This is fired when the item is published,
	 * unpublished, archived, or unarchived from the list view.
	 *
	 * @param   string   $context  The context for the content passed to the plugin.
	 * @param   array    $pks      An array of primary key ids of the content that has changed state.
	 * @param   integer  $value    The value of the state that the content has been changed to.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function onFinderChangeState($context, $pks, $value)
	{
		// We only want to handle joodb here.
		if ($context === 'com_joodb.entry') {
			$this->itemStateChange($pks, $value);
		}

		// Handle when the plugin is disabled.
		if ($context === 'com_plugins.plugin' && $value === 0) {
			$this->pluginDisable($pks);
		}
	}

	/**
	 * Method to get a content item to index.
	 *
	 * @param   integer  $id  The id of the content item.
	 *
	 * @return  Result  A Result object.
	 *
	 * @since   2.5
	 * @throws  \Exception on database error.
	 */
	protected function getItem($id)
	{
		// Get the list query and add the extra WHERE clause.
		$query = $this->getListQuery();
		$query->where('a.'.$this->jooDb->fid.' = ' . (int) $id);

		// Get the item to index.
		$this->db->setQuery($query);
		$item = $this->db->loadAssoc();

		// Convert the item to a result object.
		$item = ArrayHelper::toObject((array) $item, Result::class);

		// Set the item type.
		$item->type_id = $this->type_id;

		// Set the item layout.
		$item->layout = $this->layout;

		return $item;
	}

	/**
	 * Method to index an item. The item must be a Result object.
	 *
	 * @param   Result  $item  The item to index as a Result object.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  \Exception on database error.
	 */
	protected function index(Result $item)
	{

		// Check if the extension is enabled.
		if (ComponentHelper::isEnabled($this->extension) === false) {
			return;
		}

		$j = &$this->jooDb;

		// Create a URL as identifier to recognise items again.
		$app = Factory::getApplication();
		// generate link to the item
		$falias=$j->getSubdata('falias');
		if (!empty($app->get('sef')) && !empty($falias) && !empty($item->slug)) {
			$slug = $item->slug;
		} else {
			$slug = $item->id.':'.OutputFilter::stringURLSafe($item->title);
		}
		$item->url = "index.php?option=com_joodb&view=article&joobase=".$j->id."&id=".$slug."&Itemid=".$j->Itemid;

		// Build the necessary route and path information.
		$item->route = $item->url;

		$item->category = $j->name;

		/*
		 * Add the metadata processing instructions based on the joodb
		 * configuration parameters.
		 */

		// Get taxonomies to display
		$taxonomies = $this->params->get('taxonomies', ['type']);
		if (is_string($taxonomies)) $taxonomies = array($taxonomies);

		// Add the taxonomy data.
		if (\in_array('type', $taxonomies)) {
			$item->addTaxonomy('Type', $j->name);
		}

		if (!empty($j->fdate) && \in_array('Date', $taxonomies)) {
			$item->addTaxonomy('Date', $item->{$j->fdate});
		}

		$fuser=$j->getSubdata('fuser');
		if (!empty($fuser) && \in_array('Author', $taxonomies)) {
			$item->addTaxonomy('Author', $item->author);
		}

		// Index the item.
		$this->indexer->index($item);
	}

	/**
	 * Method to setup the indexer to be run.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.5
	 */
	protected function setup() {

		if (empty($this->jooDb)) {
			// Initialize variables
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$this->jooDb = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
			$jdbId = (int) $this->params->get('database','1');
			$this->jooDb->load($jdbId);
			$db = $this->getDatabase();
			$db->setQuery("SELECT id FROM #__menu WHERE published=1 "
				." AND link LIKE 'index.php?option=com_joodb&view=catalog%'"
				." AND ( params LIKE 'joobase=".$jdbId."%'"
				." OR params LIKE '{\"joobase\":\"".$jdbId."\"%' )");
			$this->jooDb->Itemid=$db->loadResult();
			$this->db = $this->jooDb->getTableDBO();
			$this->table = $this->jooDb->table;
		}

		return true;
	}

	/**
	 * Method to get the SQL query used to retrieve the list of content items.
	 *
	 * @param   mixed  $query  A DatabaseQuery object or null.
	 *
	 * @return  DatabaseQuery  A database object.
	 *
	 * @since   2.5
	 */
	protected function getListQuery($query = null)
	{
		// Run the setup method.
		$this->setup();
		$db = $this->getDatabase();
		$j = &$this->jooDb;

		// Check if we can use the supplied SQL query.
		$query = $query instanceof DatabaseQuery ? $query : $db->getQuery(true)
			->select("a.".$j->fid." AS id, a.".$j->ftitle." AS title, a.".$j->fcontent." AS body")
			->select("'1' AS access, '*' AS language");

		if (!empty($j->fstate)) {
			$query->select('a.'.$j->fstate.' AS state');
		}
		if (!empty($j->fdate)) {
			$query->select('a.'.$j->fdate.' AS created');
			$query->select('a.'.$j->fdate.' AS publish_start_date');
		}
		if (!empty($j->fabstract)) {
			$query->select('a.'.$j->fabstract.' AS summary');
		}
		if (!empty($j->fstate)) {
			$query->select('a.'.$j->fstate.' AS published');
		} else {
			$query->select("'1' AS published");
		}
		$falias=$j->getSubdata('falias');
		if (!empty($falias)) {
			// Handle the alias CASE WHEN portion of the query
			$case_when_item_alias = ' CASE WHEN ';
			$case_when_item_alias .= $query->charLength('a.'.$falias, '!=', '0');
			$case_when_item_alias .= ' THEN ';
			$a_id = $query->castAsChar('a.'.$j->fid);
			$case_when_item_alias .= $query->concatenate([$a_id, 'a.'.$falias], ':');
			$case_when_item_alias .= ' ELSE ';
			$case_when_item_alias .= $a_id . ' END as slug';
			$query->select($case_when_item_alias);
			$query->select('a.'.$falias.' AS alias');
		}
		$query->from("`".$j->table."` AS a");

		$fuser=$j->getSubdata('fuser');
		if (!empty($fuser))
		{
			$query->select('u.name AS author')
				->join('LEFT', '#__users AS u ON u.id = a.' . $fuser);
		}
		return $query;
	}
}
