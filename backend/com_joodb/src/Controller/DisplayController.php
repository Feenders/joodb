<?php
/**
 * @version     5.0.0
 * @package     com_joodb
 * @copyright   Copyright (C) 2011-2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Dirk Hoeschen - Feenders <hoeschen@feenders.de> - http://www.feenders.de
 */
namespace Feenders\Component\Joodb\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Feenders\Component\Joodb\Administrator\Helper\JoodbAdminHelper;
use Feenders\Component\Joodb\Administrator\Helper\FormHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
use stdClass;
use function defined;

/**
 * Main Contoller
 */
class DisplayController extends AdminController
{
	protected $default_view = 'joodb';

	/**
	 * Constructor
	 */
	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSWebApplicationInterface $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		// Register Extra tasks
		$this->registerTask( 'add','edit' );
		$this->registerTask( 'apply',	'save' );
		$this->registerTask( 'applydata',	'savedata' );
		$this->registerTask( 'savecopydata',	'savedata' );
	}

	public function display($cachable = false, $urlparams = [])
	{
		$document   = $this->app->getDocument();
		$viewType   = $document->getType();
		if ($viewType == 'html') {
			JoodbAdminHelper::prepareDocument();
		}
		return parent::display($cachable, $urlparams);
	}

	/** edit Database */
	public function edit()
	{
		JoodbAdminHelper::prepareDocument();
		$document = Factory::getDocument();
		$vType	= $document->getType();
		$view = $this->getView( 'joodbentry', $vType);
		$vLayout = $this->input->get( 'layout', 'default' );
		$view->setLayout($vLayout);
		$view->display();
	}


	/** add New entry */
	public function addNew(){
		parent::display();
	}

	/**
	 * Save data entry in joodb data table
	 */
	function savedata()
	{
		// Check for request forgeries.
		$this->checkToken();

		// load the jooDb object with table field infos
		$joodbid = $this->input->getInt( 'joodbid',1);
		$task = $this->input->get( 'task' );
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$jb = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
		$jb->load( $joodbid );
		$db	= $jb->getTableDBO();
		$id = $this->input->getInt($jb->fid);
		$isNew = empty($id);
		$item = new \stdClass();
		$copy = ($task=='savecopydata') ? true : false;
		if (FormHelper::saveData($jb,$item,$copy))
		{

			$id = $item->{$jb->fid};

			// Delete exiting image
			if ($this->input->getInt('delete_image',0) == 1)
			{
				$image = JPATH_ROOT . "/images/joodb/db" . $jb->id . "/img" . $id;
				@unlink($image . ".jpg");
				@unlink($image . "-thumb.jpg");
			}

			// attach and resize uploaded image
			// Get the uploaded file information
			$newimage = $this->input->files->get('dataset_image');
			if (!empty($newimage['name']))
			{
				// Make sure that file uploads are enabled in php
				if (!(bool) ini_get('file_uploads'))
				{
					Factory::getApplication()->enqueueMessage(Text::_('WARNINSTALLFILE'), 'warning');
					return false;
				}
				$destination = JPATH_ROOT . "/images/joodb/db" . $jb->id . "/img" . $id;
				$org_img     = $destination . "-original" . strrchr($newimage['name'], ".");
				$params      = new Registry($jb->params);
				// Move uploaded image
				$uploaded = File::upload($newimage['tmp_name'], $org_img);
				if ($uploaded && file_exists($org_img))
				{
					chmod($org_img, 0664);
					// normal image
					JoodbAdminHelper::resizeImage($org_img, $destination . ".jpg", $params->get("img_width", 480), $params->get("img_height", 600));
					// thumbnail image
					JoodbAdminHelper::resizeImage($org_img, $destination . "-thumb.jpg", $params->get("thumb_width", 120), $params->get("thumb_height", 200));
				}
			}

			// store values from subtemplates
			$subdata = $this->input->get("jbSubForm", null, "array");
			if (!empty($subdata))
			{
				$subitems = $jb->getSubitems();
				foreach ($subdata AS $name => $sdfield)
				{
					$subitem = $subitems[$name];
					if ($subitem->type == "2")
					{ // n:m relation
						// clear index from id
						$db->setquery("DELETE FROM `" . $subitem->idx_table . "` WHERE `" . $subitem->idx_id1 . "`=" . $db->quote($id))->execute();
						//rebuild index with data
						foreach ($sdfield AS $sdval)
						{
							$sdv = new stdClass();
							$sdv->{$subitem->idx_id1} = $id;
							$sdv->{$subitem->idx_id2} = $sdval;
							$db->insertObject($subitem->idx_table, $sdv, "id");
						}
					}
				}
			}

			// Trigger the onFinderAfterSave event.
			if (PluginHelper::importPlugin('finder', 'joodb', true, $this->getDispatcher())) {
				$jb->item_id = $id;
				$this->getDispatcher()->dispatch('onFinderAfterSave', new FinderEvent\AfterSaveEvent('onFinderAfterSave', ['com_joodb.entry', $jb,$isNew]));
			}
		}
		$link = 'index.php?option=com_joodb&joodbid='.$jb->id.(($task=="applydata" || $task=="savecopydata") ? "&view=editdata&cid[]=".$id : "&view=listdata");
		$this->setRedirect( $link);
		return true;
	}

	/**
	 * Save joodb entry
	 */
	public function save()
	{
		// Check for request forgeries.
		$this->checkToken();

		// Initialize variables
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$table = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);

		if (!$table->bind($_POST)) {
			throw new Exception($table->getError(),500);
		}

		$msg = Text::_("JDB_ITEM_SAVED");

		if (!$table->check()) $msg = $table->getError();
		if (!$table->store())  {
			throw new Exception($table->getError(),500);
		}

		$table->checkin();

		$task = $this->input->get( 'task' );
		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_joodb&task=edit&view=joodbentry&cid[]='. $table->id ;
				break;
			case 'save':
			default:
				$link = 'index.php?option=com_joodb';
				break;
		}

		$this->setRedirect( $link, $msg );
	}

	public function cancel()
	{
		//cancel editing a record
		$this->setRedirect( 'index.php?option=com_joodb', Text::_("JDB_EDIT_CANCELED") );
	}

	public function cancelEditData()
	{
		//cancel editing a record get database
		$this->setRedirect( 'index.php?option=com_joodb', Text::_("JDB_EDIT_CANCELED") );
	}

	public function exitjoodb()
	{
		$this->setRedirect( 'index.php' );
	}

	/**
	 * Copy one or more databases
	 */
	public function copy() {
		// Check for request forgeries.
		$this->checkToken();

		$cid	= $this->input->post->get( 'cid', array(), 'array');
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$table = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);

		if (!empty($cid))
		{
			foreach ($cid as $id)
			{
				if ($table->load( (int)$id ))
				{
					$table->id = 0;
					$table->title = 'Copy of ' . $table->name;

					if (!$table->store()) {
						throw new Exception($table->getError(),500);
					}
				}
				else {
					throw new Exception($table->getError(),500);
				}
			}
		} else {
			$this->setMessage(Text::_( 'JDB_NO_ITEMS_SELECTED' ));
		}
		$this->setMessage( Text::sprintf( 'JDB_ITEMS_COPIED', count($cid) ) );
	}

	/**
	 * Remove entries from joodb database tables
	 */
	public function removedata() {
		// Check for request forgeries.
		$this->checkToken();

		$joodbid = $this->input->getInt( 'joodbid');

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$table = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
		$table->load( $joodbid );

		$this->setRedirect( 'index.php?option=com_joodb&view=listdata&joodbid='.$table->id );

		// Initialize variables
		$db	= $table->getTableDBO();
		$cid	= $this->input->post->get( 'cid', null, 'array');
		$n		= count( $cid );
		ArrayHelper::toInteger( $cid );

		if (count($cid) < 1) {
			$this->setMessage(Text::_( 'JDB_NO_ITEMS_SELECTED' ));
		} else {
			$cids = implode(',', $cid);
			$query = 'DELETE FROM '.$table->table
				. ' WHERE '.$table->fid.' IN ( '. $cids. ' )';
			$db->setQuery( $query );
			if (!$db->execute()) {
				Factory::getApplication()->enqueueMessage($db->getError(),"error");
			}
		}

		$this->setMessage( Text::sprintf( 'JDB_ITEMS_REMOVED', $n ) );
	}

	/**
	 * Sets the publish state of a jodb data table entry to 1 ...
	 */
	public function data_publish() {
		// Check for request forgeries.
		$this->checkToken();

		$joodbid	= $this->input->getInt( 'joodbid');
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$table = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
		$table->load( $joodbid );

		// Initialize variables
		$db	= $table->getTableDBO();
		$cid	= $this->input->post->get( 'cid', null, 'array');
		$n		= count( $cid );
		ArrayHelper::toInteger( $cid );

		if ($n) {
			$cids = implode(',', $cid);
			$query = 'UPDATE '.$table->table.' SET '.$table->fstate.'=1'
				. ' WHERE '.$table->fid.' IN ( '. $cids. ' )';
			$db->setQuery( $query );
			if (!$db->execute()) {
				throw new Exception($db->getError(),500);
			}
		}

		$this->setRedirect( 'index.php?option=com_joodb&view=listdata&joodbid='.$table->id );
		$this->setMessage( Text::sprintf( 'JDB_ITEMS_PUBLISHED', $n ) );
	}

	/**
	 * Sets the publish state of a jodb data table entry to 0 ...
	 */
	public function data_unpublish() {
		// Check for request forgeries.
		$this->checkToken();

		$joodbid	= $this->input->getInt( 'joodbid');
		$jb = Table::getInstance('JoodbTable', '\\Feenders\\Component\\Joodb\\Administrator\\Table\\');
		$jb->load( $joodbid );

		// Initialize variables
		$db	= $jb->getTableDBO();
		$cid	= $this->input->post->get( 'cid', null, 'array');
		$n		= count( $cid );
		ArrayHelper::toInteger( $cid );

		if ($n) {
			$cids = implode(',', $cid);
			$query = 'UPDATE '.$jb->table.' SET '.$jb->fstate.'=0'
				. ' WHERE '.$jb->fid.' IN ( '. $cids. ' )';
			$db->setQuery( $query );
			if (!$db->execute()) {
				throw new Exception($db->getError(),500);
			}
		}

		$this->setRedirect( 'index.php?option=com_joodb&view=listdata&joodbid='.$jb->id );
		$this->setMessage( Text::sprintf( 'JDB_ITEMS_UNPUBLISHED', $n ) );
	}


	/**
	 * Remove item(s)
	 */
	public function remove($view='joodb') {
		// Check for request forgeries.
		$this->checkToken();

		$this->setRedirect( 'index.php?option=com_joodb' );

		// Initialize variables
		$db		= Factory::getContainer()->get(DatabaseInterface::class);
		$cid	= $this->input->post->get( 'cid', array(), 'array');
		ArrayHelper::toInteger( $cid );

		if (count($cid) < 1) {
			$this->setMessage(Text::_('Select an item to delete'));
		} else {
			$query = 'DELETE FROM `#__joodb`'
				. ' WHERE id = ' . implode( ' OR id = ', $cid );
			$db->setQuery( $query );
			if (!$db->execute()) {
				throw new Exception($db->getError(),500);
			}
			$this->setMessage( Text::sprintf( 'JDB_ITEMS_REMOVED', count( $cid )));
		}
	}


	/**
	 * Un Publish item(s)
	 */
	public function unpublish() {
		// Check for request forgeries.
		$this->checkToken();

		$db		= Factory::getContainer()->get(DatabaseInterface::class);
		$cid	= $this->input->post->get( 'cid', null, 'array');
		$n		= count( $cid );
		ArrayHelper::toInteger( $cid );

		if ($n) {
			$query = 'UPDATE #__joodb SET published=0 '
				. ' WHERE id = ' . implode( ' OR id = ', $cid );
			$db->setQuery( $query );
			if (!$db->execute()) {
				throw new Exception($db->getError(),500);
			} else {
				$msg = Text::sprintf( 'JDB_ITEMS_UNPUBLISHED', count( $cid ) );
			}
		}
		$this->setRedirect( 'index.php?option=com_joodb&task=display', $msg );
	}

	/**
	 * Publish item(s)
	 */
	public function publish()	{
		// Check for request forgeries.
		$this->checkToken();

		$db		= Factory::getContainer()->get(DatabaseInterface::class);
		$cid	= $this->input->post->get( 'cid', null, 'array');
		$data  = ['publish' => 1, 'unpublish' => 0];
		$task  = $this->getTask();
		$value = $data[$task];
		ArrayHelper::toInteger( $cid );

		if (!empty($cid)) {
			$query = "UPDATE #__joodb SET published= ".$value." WHERE id = " . implode( ' OR id = ', $cid );
			$db->setQuery( $query );
			if (!$db->execute()) {
				throw new Exception($db->getError(),500);
			} else {
				$msg = ($value==0) ? "JDB_ITEMS_UNPUBLISHED" : "JDB_ITEMS_PUBLISHED";
				$msg = Text::sprintf( $msg, count( $cid ) );
			}
		}
		$this->setRedirect( 'index.php?option=com_joodb&task=display', $msg );
	}

	/**
	 * Test the existance of a table
	 */
	public function  testtable() {
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		if ($tname = $this->input->get("table"))
			$tables = $db->getTableList();
		$exist = (array_search($tname, $tables)!==false) ? true : false;
		header('Content-type: application/json');
		echo json_encode($exist);
		die();
	}

	/**
	 * Tests an sql connection and retuns database names
	 */
	public function  testconnection() {
		$dbs = array();
		$link = @mysqli_connect($this->input->getString("extdb_server"), $this->input->getString("extdb_user"), $this->input->getString( "extdb_pass"),null);
		if ($link) {
			$db_list = mysqli_query($link,"SHOW DATABASES");
			while ($row = mysqli_fetch_assoc($db_list)) $dbs[] = $row['Database'];
		}
		header('Content-type: application/json');
		if (!empty($dbs))
			echo '{"dbs":'.json_encode($dbs)."}";
		else if ($link)
			echo '{"connected": "true"}';
		else
			echo '{"error": "true"}';
		die();
	}

	/**
	 * Get Tablefildlist from a Table of JooDB Database
	 */
	public function getfieldlist() {
		header('Content-type: application/json');
		if ($id = $this->input->getInt('jbid')) {
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$table = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
			if ($table->load( $id )) {
				$tdb = $table->getTableDBO();
				$tdb->setQuery("SHOW COLUMNS FROM `".$tdb->escape($this->input->getString('table'))."`");
				try {
					$fields = $tdb->loadObjectList();
					$response = '{"fields":'.json_encode($fields)."}";
				} catch (\RuntimeException $e) {
					$response = '{"error":"'.$e->getMessage().'"}';
				}
			} else { $response = '{"error":"could not load table"}'; }
		} else { $response = '{"error":"no id"}'; }
		echo $response;
		die();
	}

	/**
	 * Activate the joodb copy
	 */
	public function activate() {
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$db->setQuery("DELETE FROM `#__joodb_settings` WHERE `name` = 'license' AND `jb_id` IS NULL");
		$db->execute();
		$v = array();
		$v['key'] = $this->input->getString("key");
		$v['domain'] = $this->input->getString("domain");
		$v['hash'] = $this->input->getString("hash");
		$item = new stdClass();
		$item->name = "license";
		$item->value = json_encode($v);
		$db->insertObject("#__joodb_settings", $item,"id");
		$this->setRedirect('index.php?option=com_joodb&task=display&view=info',Text::_("JDB_SUCCESSFULLY_ACTIVATED"));
	}
}
