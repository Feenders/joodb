<?php
/** part of JooBatabase component - see http://joodb.feenders.de */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/**
 * Import Contoller
 */
class JoodbControllerImport extends JooDBController {

	protected $output;
	protected $errors;

	/**
	 * Constructor
	 */
	public function __construct( $config = array() )
	{
		parent::__construct( $config );
		$this->output = (object) array('header'=>JText::_("PROCESSING"),'message'=>null,'finished'=>false,'error'=>false);
		$this->errors = array();

		@ini_set("memory_limit","256M");
		@ini_set('max_execution_time', 600);
	}
	
	/**
	* Import excel file / load and parse structure first
	*/
	public function import() {
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$model = $this->getModel("import");
		
		$file = JFactory::getApplication()->input->files->get('tablefile');
		if (!empty($file["name"])) {
			$model->importSheet($file);
			if (!empty($model->errors)) {
				$this->errors[] = join('<br/>',$model->errors);
			} else {
				$this->output->message = JText::sprintf("GETTING_N_TO_N", $model->startRow, ($model->startRow+$model->chunksize), $model->highestRow);
			}
			$model->xportToSession();
		} else {
			$this->errors[] = JText::_("UPLOAD_VALID_EXCEL_FILE");
		}
		$this->sendResponse();
	}
	
	/**
	 * Get next chunk from table / data is passed by model
	 */
	public function importchunk() {
		$model = $this->getModel("import");
		$session = JFactory::getSession();
		$importdata = json_decode($session->get('importdata','[]'),true);
        foreach ($importdata AS $var => $val) $model->{$var} = $val;
		$model->importChunk();
		if (!empty($model->errors)) {
			$this->errors[] = join('<br/>',$model->errors);
		} else if ($model->finished) {
			$this->output->header = JText::_("READY");
			$this->output->finished = true;
			$this->output->message = JText::_("Table imported").' »'.$model->tablename.'«';
			$this->output->message .= '<br/><div class="btn btn-success" onclick="window.top.location.reload();"><i class="icon-thumbs-up" style="color: #fff;"></i>&nbsp;'.JText::_('close').'</div>';
		} else {
			$model->xportToSession();
			$this->output->message = JText::sprintf("GETTING_N_TO_N", $model->startRow, ($model->startRow+$model->chunksize), $model->highestRow);
		}
		$this->sendResponse();
	}

	/**
	 * Send output and close app
	 *
	 * @since version
	 */
	private function sendResponse() {
		header('Content-type: application/json');
		if (!empty($this->errors)) {
			$this->output->header = JText::_("ERROR");
			$this->output->message = '<div class="text-danger">'.join('<br/>',$this->errors).'</div>';
		}
		echo json_encode($this->output);
		JFactory::getApplication()->close();
	}
	
}
