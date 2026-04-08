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

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Feenders\Component\Joodb\Administrator\Model\ImportModel;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;
use function defined;

/**
 * Main Contoller
 */
class ImportController extends AdminController {

	protected $output;
	protected $errors;

	/**
	 * Constructor
	 */
	// constructor - registers additional tasks to methods
	public function __construct($config = [] , ?MVCFactoryInterface $factory = null, ?CMSApplicationInterface $app = null, ?Input $input = null) {

		// Register Extra tasks
		parent::__construct($config, $factory, $app, $input);
		$this->output = (object) array('header'=>Text::_("JDB_PROCESSING"),'message'=>null,'finished'=>false,'error'=>false);
		$this->errors = array();

		@ini_set("memory_limit","256M");
		@ini_set('max_execution_time', 600);
	}
	
	/**
	* Import excel file / load and parse structure first
	*/
	public function import() {
		// Check for request forgeries.
		$this->checkToken();

		$model = $this->getModel("Import","",array());
		$file = $this->input->files->get('tablefile');
		if (!empty($file["name"])) {
			$model->importSheet($file);
			if (!empty($model->errors)) {
				$this->errors[] = join('<br/>',$model->errors);
			} else {
				$this->output->message = Text::sprintf("JDB_GETTING_N_TO_N", $model->startRow, ($model->startRow+$model->chunksize), $model->highestRow);
			}
			$model->xportToSession();
		} else {
			$this->errors[] = Text::_("JDB_UPLOAD_VALID_EXCEL_FILE");
		}
		$this->sendResponse();
	}
	
	/**
	 * Get next chunk from table / data is passed by model
	 */
	public function importchunk() {
		$model = $this->getModel("Import","",array());
		$session = Factory::getApplication()->getSession();
		$importdata = json_decode($session->get('importdata','[]'),true);
        foreach ($importdata AS $var => $val) $model->{$var} = $val;
		$model->importChunk();
		if (!empty($model->errors)) {
			$this->errors[] = join('<br/>',$model->errors);
		} else if ($model->finished) {
			$this->output->header = Text::_("JDB_READY");
			$this->output->finished = true;
			$this->output->message = Text::_("JDB_TABLE_IMPORTED").' »'.$model->tablename.'«';
			$this->output->message .= '<br/><div class="btn btn-success" onclick="window.top.location.reload();"><i class="icon-thumbs-up" style="color: #fff;"></i>&nbsp;'.Text::_("JDB_CLOSE").'</div>';
		} else {
			$model->xportToSession();
			$this->output->message = Text::sprintf("JDB_GETTING_N_TO_N", $model->startRow, ($model->startRow+$model->chunksize), $model->highestRow);
		}
		$this->sendResponse();
	}

	/**
	 * Send output and close app
	 *
	 */
	private function sendResponse() {
		header('Content-type: application/json');
		if (!empty($this->errors)) {
			$this->output->header = Text::_("JDB_ERROR");
			$this->output->message = '<div class="text-danger">'.join('<br/>',$this->errors).'</div>';
		}
		echo json_encode($this->output);
		Factory::getApplication()->close();
	}
	
}
