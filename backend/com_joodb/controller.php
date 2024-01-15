<?php
/** part of JooBatabase component - see http://joodb.feenders.de */
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Main Contoller
 */
class JooDBController extends AdminController
{
	/**
	 * Constructor
	 */
	public function __construct( $config = array() )
	{
		parent::__construct( $config );
		// Register Extra tasks
		$this->registerTask( 'add','edit' );
		$this->registerTask( 'apply',	'save' );
		$this->registerTask( 'applydata',	'savedata' );
		$this->registerTask( 'savecopydata',	'savedata' );
	}


	/** edit Database */
	public function edit()
	{
		$document = JFactory::getDocument();
		$vType	= $document->getType();
		$view = $this->getView( 'joodbentry', $vType);
		$vLayout = JFactory::getApplication()->input->get( 'layout', 'default' );
		$view->setLayout($vLayout);
		$view->display();
	}



	/** edit Data of JooDatabase tables */
	public function editdata() {
		$document = JFactory::getDocument();
		$vType	= $document->getType();
		$view = $this->getView( 'editdata', $vType);
		$vLayout = JFactory::getApplication()->input->get( 'layout', 'default' );
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
		require_once( JPATH_COMPONENT_ADMINISTRATOR.'/helpers/form.php' );

		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// load the jooDb object with table field infos
		$joodbid = $this->input->getInt( 'joodbid',1);
		$task = $this->input->get( 'task' );
		$jb = JTable::getInstance( 'joodb', 'Table' );
		$jb->load( $joodbid );
		$db	= $jb->getTableDBO();
		$id = $this->input->getInt($jb->fid);
		$item = new JObject();
		$copy = ($task=='savecopydata') ? true : false;
		if (JoodbFormHelper::saveData($jb,$item,$copy))
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
					JFactory::getApplication()->enqueueMessage(JText::_('WARNINSTALLFILE'), 'warning');
					return false;
				}
				$destination = JPATH_ROOT . "/images/joodb/db" . $jb->id . "/img" . $id;
				$org_img     = $destination . "-original" . strrchr($newimage['name'], ".");
				$params      = new JRegistry($jb->params);
				// Move uploaded image
				jimport('joomla.filesystem.file');
				$uploaded = JFile::upload($newimage['tmp_name'], $org_img);
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
		}
		$link = 'index.php?option=com_joodb&joodbid='.$jb->id.(($task=="applydata" || $task=="savecopydata") ? "&view=editdata&cid[]=".$id : "&view=listdata");
		$this->setRedirect( $link);
	}

	/**
	 * Save joodb entry
	 */
	public function save()
	{

		// Initialize variables
		$row = JTable::getInstance('joodb', 'Table');

		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		if (!$row->bind($_POST)) {
			throw new Exception($row->getError(),500);
		}

		$msg = JText::_( 'Item Saved' );

		if (!$row->check()) $msg = $row->getError();
		if (!$row->store())  {
			throw new Exception($row->getError(),500);
		}

		$row->checkin();

		$task = $this->input->get( 'task' );
		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_joodb&task=edit&view=joodbentry&cid[]='. $row->id ;
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
		$this->setRedirect( 'index.php?option=com_joodb', JText::_( 'Edit canceled' ) );
	}

	public function cancelEditData()
	{
		//cancel editing a record get database
		$this->setRedirect( 'index.php?option=com_joodb', JText::_( 'Edit canceled' ) );
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
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$input = JFactory::getApplication()->input;

		$cid	= $this->input->post->get( 'cid', null, 'array');
		$item  = JTable::getInstance('joodb', 'Table');
		$n		= count( $cid );

		if ($n > 0)
		{
			foreach ($cid as $id)
			{
				if ($item->load( (int)$id ))
				{
					$item->id = 0;
					$item->title = 'Copy of ' . $item->name;

					if (!$item->store()) {
						throw new Exception($item->getError(),500);
					}
				}
				else {
					throw new Exception($item->getError(),500);
				}
			}
		} else {
			$this->setMessage(JText::_( 'No items selected' ));
		}
		$this->setMessage( JText::sprintf( 'Items copied', $n ) );
	}

	/**
	 * Remove entries from joodb database tables
	 */
	public function removedata() {
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$joodbid = $this->input->getInt( 'joodbid');

		$jb = JTable::getInstance( 'joodb', 'Table' );
		$jb->load( $joodbid );

		$this->setRedirect( 'index.php?option=com_joodb&view=listdata&joodbid='.$jb->id );

		// Initialize variables
		$db	= $jb->getTableDBO();
		$cid	= $this->input->post->get( 'cid', null, 'array');
		$n		= count( $cid );
		ArrayHelper::toInteger( $cid );

		if (count($cid) < 1) {
			$this->setMessage(JText::_('Select an item to delete'));
		} else {
			$cids = implode(',', $cid);
			$query = 'DELETE FROM '.$jb->table
				. ' WHERE '.$jb->fid.' IN ( '. $cids. ' )';
			$db->setQuery( $query );
			if (!$db->execute()) {
				Error::raiseWarning( 500, $db->getError() );
			}
		}

		$this->setMessage( JText::sprintf( 'Items removed', $n ) );
	}

	/**
	 * Sets the publish state of a jodb data table entry to 1 ...
	 */
	public function data_publish() {
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));;

		$joodbid	= $this->input->getInt( 'joodbid');
		$jb = JTable::getInstance( 'joodb', 'Table' );
		$jb->load( $joodbid );

		// Initialize variables
		$db	= $jb->getTableDBO();
		$cid	= $this->input->post->get( 'cid', null, 'array');
		$n		= count( $cid );
		ArrayHelper::toInteger( $cid );

		if ($n) {
			$cids = implode(',', $cid);
			$query = 'UPDATE '.$jb->table.' SET '.$jb->fstate.'=1'
				. ' WHERE '.$jb->fid.' IN ( '. $cids. ' )';
			$db->setQuery( $query );
			if (!$db->execute()) {
				throw new Exception($db->getError(),500);
			}
		}

		$this->setRedirect( 'index.php?option=com_joodb&view=listdata&joodbid='.$jb->id );
		$this->setMessage( JText::sprintf( 'ITEMS_PUBLISHED', $n ) );
	}

	/**
	 * Sets the publish state of a jodb data table entry to 0 ...
	 */
	public function data_unpublish() {
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$joodbid	= $this->input->getInt( 'joodbid');
		$jb = JTable::getInstance( 'joodb', 'Table' );
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
		$this->setMessage( JText::sprintf( 'ITEMS_UNPUBLISHED', $n ) );
	}


	/**
	 * Remove item(s)
	 */
	public function remove($view='joodb') {
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$this->setRedirect( 'index.php?option=com_joodb' );

		// Initialize variables
		$db		= JFactory::getDBO();
		$cid	= $this->input->post->get( 'cid', null, 'array');
		ArrayHelper::toInteger( $cid );

		if (count($cid) < 1) {
			$this->setMessage(JText::_('Select an item to delete'));
		} else {
			$query = 'DELETE FROM `#__joodb`'
				. ' WHERE id = ' . implode( ' OR id = ', $cid );
			$db->setQuery( $query );
			if (!$db->execute()) {
				throw new Exception($db->getError(),500);
			}
			$this->setMessage( JText::sprintf( 'Items removed', count( $cid )));
		}
	}


	/**
	 * Un Publish item(s)
	 */
	public function unpublish() {
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$db		= JFactory::getDBO();
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
				$msg = JText::sprintf( 'ITEMS_UNPUBLISHED', count( $cid ) );
			}
		}
		$this->setRedirect( 'index.php?option=com_joodb&task=display', $msg );
	}

	/**
	 * Publish item(s)
	 */
	public function publish()	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$db		= JFactory::getDBO();
		$cid	= $this->input->post->get( 'cid', null, 'array');
		$n		= count( $cid );
		ArrayHelper::toInteger( $cid );

		if ($n) {
			$query = 'UPDATE #__joodb SET published=1 '
				. ' WHERE id = ' . implode( ' OR id = ', $cid );
			$db->setQuery( $query );
			if (!$db->execute()) {
				throw new Exception($db->getError(),500);
			} else {
				$msg = JText::sprintf( 'ITEMS_PUBLISHED', count( $cid ) );
			}
		}
		$this->setRedirect( 'index.php?option=com_joodb&task=display', $msg );
	}

	/**
	 * Test the existance of a table
	 */
	public function  testtable() {
		$db = JFactory::getDbo();
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
		};
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
			$row = JTable::getInstance( 'joodb', 'Table' );
			if ($row->load( $id )) {
				$tdb = $row->getTableDBO();
				$tdb->setQuery("SHOW COLUMNS FROM `".$tdb->escape($this->input->getString('table'))."`");
				try {
					$fields = $tdb->loadObjectList();
					$response = '{"fields":'.json_encode($fields)."}";
				} catch (RuntimeException $e) {
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
		$db = JFactory::getDbo();
		$db->setQuery("DELETE FROM `#__joodb_settings` WHERE `name` = 'license' AND `jb_id` IS NULL");
		$db->execute();
		$v = array();
		$v['key'] = $this->input->getString("key");
		$v['domain'] = $this->input->getString("domain");
		$v['hash'] = $this->input->getString("hash");
		$item = new JObject();
		$item->name = "license";
		$item->value = json_encode($v);
		$db->insertObject("#__joodb_settings", $item,"id");
		$this->setRedirect('index.php?option=com_joodb&task=display&view=info',JText::_('SUCCESSFULLY ACTIVATED'));
	}
}
