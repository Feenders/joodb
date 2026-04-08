<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

namespace Feenders\Component\Joodb\Site\Model;

defined('_JEXEC') or die();

use Exception;
use Feenders\Component\Joodb\Site\Helper\JoodbHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use RuntimeException;
use stdClass;
use function defined;

/**
 * JooDatabase Component edit or add Catalog Item Model
 */
class FormModel extends BaseDatabaseModel {

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
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{	parent::__construct($config, $factory);

		$app = Factory::getApplication();
		$params	= $app->getParams();
		$joobase = $params->get("joobase",0);
		// Load the Database parameters
		if ($joobase==0) $joobase = $app->input->getInt('joobase', 1);
		$this->_joobase = Table::getInstance('JoodbTable', '\\Feenders\\Component\\Joodb\\Administrator\\Table\\');
		if (!$this->_joobase->load( $joobase)) {
			throw new Exception($this->_joobase->getError(),500);
		}
		if ($this->_joobase->published==0) throw new RuntimeException( 'Database is unpublished or not availiable',404);

		$this->_db = $this->_joobase->getTableDBO();

		// get the table field list with fieldinfo
		$this->_joobase->fields = $this->_db->getTableColumns($this->_joobase->table,false);

		// access allowed... redirect to login if not
		$item = $this->getData();
		$accl = (empty($item->{$this->_joobase->fid})) ? "accessf" : "accesse";
		$has_access = JoodbHelper::checkAuthorization($this->_joobase,$accl,$item);
		if (!$has_access) throw new Exception(Text::_("JDB_ALERTNOTAUTH"), 403 );

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
     * @param id integer
	 * @access public
	 * @return array
	 */
	public function getData($id=null)
	{
		$app = Factory::getApplication();
        // Lets load the content if it doesn't already exist
        if (empty($this->_data))
        {
            /* Query single object. */
            if (empty($id)) $id = $app->input->get("id");
            if (!empty($id)) {
                $this->_db->setQuery('SELECT * FROM `'.$this->_joobase->table
                    . '` WHERE `'.$this->_joobase->fid.'`='.$this->_db->quote($id).' LIMIT 1;');
                $this->_data = $this->_db->loadObject();
            }
        }

        // empty object if not found
        if (empty($this->_data)) {
            $this->_data = new stdClass();
            foreach ($this->_joobase->fields AS &$field)
                $this->_data->{$field->Field} = null;
        }

        return $this->_data;
	}

}

