<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

use Joomla\CMS\MVC\Controller\BaseController;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );


/**
 * JooDatabase Component Controller
 */
class JoodbController extends BaseController
{

	public function __construct( array $config = array() ) {
		parent::__construct( $config );
		$this->registerTask( 'copy','save' );
	}

	/**
	 * Method to show a view
	 */
	public function display($cachable = true, $urlparams = false)
	{
		$app =  JFactory::getApplication();
		$view = $app->input->get('view','catalog');

		if ($view=='catalog') {
			$cachable = false;
		}

        // TODO complete list of params
        $urlparams = array(
            'option' => 'CMD',
            'view' => 'CMD',
            'task' => 'CMD',
            'format' => 'CMD',
            'layout' => 'CMD',
            'id' => 'INT',
            'jbid' => 'INT',
            'letter' => 'CMD',
            'search' => 'STRING',
            'searchfield' => 'STRING',
            'gs' => 'ARRAY',
            'fs' => 'ARRAY',
            'orderby' => 'CMD',
            'ordering' => 'CMD',
            'reset' => 'CMD',
            'limit' => 'UINT',
            'limitstart' => 'UINT',
            'print' => 'BOOLEAN',
            'lang' => 'CMD',
            'alphachar' => 'CMD',
            'Itemid' => 'INT'
        );

		parent::display($cachable, $urlparams);
	}

	/**
	 * Submit Form Data. send email and insert to database
	 */
	public function submit()
	{
        require_once(JPATH_COMPONENT_ADMINISTRATOR.'/helpers/form.php');
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		$params	= $app->getParams();
		$Itemid = ($params->get("redirect")==1) ? $params->get("redirect_to") : $app->input->getInt("Itemid");
		// read database configuration from joobase table
		$model= $this->getModel('form');
		$jb = $model->getJoobase();
		// merge the component with the joodb parameters
		jimport('joomla.html.parameter');
		$jparams = new JRegistry($jb->params );
		$params->merge($jparams);

		// check captcha if any in form template
		if (strpos($jb->tpl_form,"{joodb captcha")!==false) {
	    	$session = JFactory::getSession();
            $code = $app->input->get('joocaptcha');
			if (!$session->get('joocaptcha') || $session->get('joocaptcha')!=$code) {
                $app->input->set('joocaptcha','');
				$app->setUserState('com_joodb.form.data',$_POST);
				$app->enqueueMessage( JText::_('Captcha code invalid'), "warning" );
				$this->setRedirect(JRoute::_('index.php?option=com_joodb&view=form&joobase='.$jb->id.'&Itemid='.$app->input->getInt("Itemid"),false));
				return;
			}
            $session->set('joocaptcha',null);
		}

		// insert form data
        $id = $app->input->get($jb->fid,null);
		$item = $model->getData($id);
        $user = JFactory::getUser();
        // Admin Users can override user id
		$fuser=$jb->getSubdata('fuser');
		if (!empty($fuser)) {
			if (isset($item->{$fuser}) && !empty($item->{$fuser}) && $item->{$fuser}!=$user->id) {
				if (!$user->authorise('core.admin')) {
					throw new RuntimeException(JText::_('ALERTNOTAUTH'),403);
				}
			} else {
				$item->{$fuser}=$user->id;
			}
		}

		if (JoodbFormHelper::saveData($jb,$item))
		{
			// send formdata to admin
			if (empty($id) && $params->get("infomail", 0) == 1)
			{
				$db->setQuery("SELECT email FROM `#__users` WHERE `id` =" . (int)$params->get("infomail_user") . " LIMIT 1");
				if ($email = $db->loadResult())
				{
					$MailFrom = $app->get('mailfrom');
					$FromName = $app->get('fromname');
					$subject  = "JooDatabase - " . JText::_('New Database entry') . " - " . $jb->name;
					$body     = $subject . "\r\n";
					$body     .= "Site: " . $app->get('sitename') . " - " . JUri::current() . "\r\n\r\n";
					$body     .= JText::_('Recieved values') . "\r\n===================\r\n";
					foreach ($item as $var => $val)
						if (!empty($val))
							$body .= ucfirst($var) . ": " . $val . "\r\n";;
					$body .= "===================\r\n\r\n";
					$mail = JFactory::getMailer();
					$mail->addRecipient($email);
					$mail->setSender(array($MailFrom, $FromName));
					$mail->setSubject($FromName . ': ' . $subject);
					$mail->setBody($body);
					$sent = $mail->Send();
					if (!$sent) $app->enqueueMessage('Mail could not be sent. Please inform admin!', 'warning');
				}

			}
			$this->getDatasetImage($jb,$item);
			JoodbFormHelper::saveSubData($jb,$item);

		}
		$this->setRedirect(JRoute::_('index.php?Itemid='.$Itemid,false));
	}

	/**
	 * Save database entry from edit form
	 */
	public function save()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
        require_once( JPATH_COMPONENT_ADMINISTRATOR.'/helpers/form.php' );

		$app = JFactory::getApplication();
		$model = $this->getModel('edit');
		$jb = $model->getJoobase();
		$id = $app->input->get($jb->fid);
		$item = $model->getData($id);
		// insert form data
		$copy = ($this->task=='copy') ? true : false;
		if (JoodbFormHelper::saveData($jb,$item,$copy))
		{
			// Delete exiting image
			if ($app->input->getInt('delete_image',0) == 1)
			{
				$image = JPATH_ROOT . "/images/joodb/db" . $jb->id . "/img" . $id;
				@unlink($image . ".jpg");
				@unlink($image . "-thumb.jpg");
			}

			$this->getDatasetImage($jb,$item);
			JoodbFormHelper::saveSubData($jb,$item);
			$view = 'article';
		} else {
			$view = 'edit';
		}
		$this->setRedirect(JRoute::_('index.php?option=com_joodb&joobase=' . $jb->id . '&view='.$view.'&id=' . $item->{$jb->fid} . '&Itemid=' . $app->input->getInt('Itemid'), false));
	}

    /**
     * Delete an enty in the frontend
     */
    public function delete() {
	    $app = JFactory::getApplication();
        $model = $this->getModel('edit');
        $jb = $model->getJoobase();
        $id = $app->input->get('id');
        $msg = JText::_("ERROR");
        if (!empty($id)) {
            $db = $jb->getTableDbo();
            $result = $db->setQuery("DELETE FROM `".$jb->table."` WHERE `".$jb->fid."` =".(int)$id)->execute();
            if (!empty($result)) $msg = JText::_("ITEM DELETED");
        }
        $this->setRedirect(JRoute::_('index.php?option=com_joodb&joobase='.$jb->id.'&view=catalog&Itemid='.$app->input->getInt('Itemid'),false), $msg);
    }


	/**
	 * Print out a captcha image
	 */
	public function captcha()
	{
		$app = JFactory::getApplication();
		JoodbHelper::printCaptcha();
		$app->close();
	}

	/**
	 * Add entries to notepad ...
	 */
	public function addToNotepad() {
		$app = JFactory::getApplication();
  		$session = JFactory::getSession();
		$articles = preg_split("/:/",$session->get('articles',''));
		if ($articles[0]=="") unset($articles[0]);
		$articles[] = $app->input->getCmd("article");
		$session->set('articles', join(":",$articles));
		$this->display();
	}

	/**
	 * Remove entries from notepad
	 */
	public function removeFromNotepad() {
		$app = JFactory::getApplication();
  		$session = JFactory::getSession();
		$articles = preg_split("/:/",$session->get('articles',''));
		if ($articles[0]=="") unset($articles[0]);
		$id = $app->input->get("article");
		foreach ($articles as $ndx => $article)
	    	if ($article==$id) {
	    		unset($articles[$ndx]);
	    	}
		$session->set('articles', join(":",$articles));
		$this->display();
	}

	/**
	 * Delete all entries from notepad
	 */
	public function purgeNotepad() {
		$session = JFactory::getSession();
		$session->set('articles', '');
		$this->display();
	}

	/**
	 * Wrapper for blob images and files
	 */
	public function getFileFromBlob() {
		$app = JFactory::getApplication();
		$model = $this->getModel("article");
		if ($item = $model->getData()) {
			if ($field = $app->input->getString('field')) {
				$mime = JoodbAdminHelper::getMimeType($item->{$field});
				if (substr($mime, 0,5)=="image") {
					$im = imagecreatefromstring($item->{$field});
					header('Content-Type: image/png');
					imagepng($im);
					imagedestroy($im);
				} else {
					$p = preg_split("/\//", $mime);
					$ext = ($mime!="application/octet-stream") ? $p[1] : "bin";
					$disp = ($mime=="application/pdf") ? "inline" : "attachment";
					$jb = $model->getJoobase();
					header("Pragma: public");
					header("Content-Type: ".$mime);
					header("Content-Disposition: ".$disp."; filename=".$field."-".JFilterOutput::stringURLSafe($item->{$jb->ftitle}).".".$ext);
					header("Content-Length: ".mb_strlen($item->{$field}, '8bit'));
					echo $item->{$field};
				}
			}
		}
		die();
	}

	/**
	 * Get anonymous registration info for validation
	 */
	public function getLicenseInfo() {
		$db = JFactory::getDbo();
		$db->setQuery("SELECT value FROM `#__joodb_settings` WHERE `name` = 'license' AND `jb_id` IS NULL",0,1);
		header('Content-type: application/json');
		$status=json_decode($db->loadResult());
		echo '{"hash": "'.$status->hash.'"}';
		die();
	}

	/**
	 * Get image and upload
	 *
	 * @param $jb
	 * @param $item
	 *
	 */
	protected function getDatasetImage(&$jb,&$item) {
		// TODO: Controller is not the right place
		$app = JFactory::getApplication();
		// Attach and resize uploaded image
		$newimage = $app->input->files->get('joodb_dataset_image');
		if (!empty($newimage['name']))
		{
			jimport('joomla.html.parameter');
			$params = new JRegistry($jb->params);
			require_once(JPATH_COMPONENT . "/helpers/files.php");
			$destination = JPATH_ROOT . "/images/joodb/db" . $jb->id . "/img" . $item->{$jb->fid};
			JoodbFilesHelper::processUploadedImage($newimage, $destination, $params);
		}

	}

}
