<?php
/**
 * @version     5.0.0
 * @package     com_joodb
 * @copyright   Copyright (C) 2011-2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Dirk Hoeschen - Feenders <hoeschen@feenders.de> - http://www.feenders.de
 */
namespace Feenders\Component\Joodb\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Feenders\Component\Joodb\Administrator\Helper\JoodbAdminHelper;
use Feenders\Component\Joodb\Administrator\Helper\FormHelper;
use Feenders\Component\Joodb\Site\Helper\FilesHelper;
use Feenders\Component\Joodb\Site\Helper\JoodbHelper;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\Filter\OutputFilter;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
use PHPMailer\PHPMailer\Exception as phpmailerException;
use function defined;

/**
 * Main Contoller
 */
class DisplayController extends BaseController {

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplicationInterface $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);
		$this->registerTask( 'copy','save' );
	}

	/**
	 * Method to show a view
	 */
	public function display($cachable = true, $urlparams = [])
	{
		$app =  Factory::getApplication();
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
		$this->checkToken();

		$app = Factory::getApplication();
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$params	= $app->getParams();
		$Itemid = ($params->get("redirect")==1) ? $params->get("redirect_to") : $app->input->getInt("Itemid");
		// read database configuration from joobase table
		$model= $this->getModel('form');
		$jb = $model->getJoobase();
		// merge the component with the joodb parameters
		$jparams = new Registry($jb->params );
		$params->merge($jparams);

		// check captcha if any in form template
		if (strpos($jb->tpl_form,"{joodb captcha")!==false) {
	    	$session = $app->getSession();
            $code = $app->input->get('joocaptcha');
			if (!$session->get('joocaptcha') || $session->get('joocaptcha')!=$code) {
                $app->input->set('joocaptcha','');
				$app->setUserState('com_joodb.form.data',$_POST);
				$app->enqueueMessage( Text::_("JDB_CAPTCHA_CODE_INVALID"), "warning" );
				$this->setRedirect(Route::_('index.php?option=com_joodb&view=form&joobase='.$jb->id.'&Itemid='.$app->input->getInt("Itemid"),false));
				return;
			}
            $session->set('joocaptcha',null);
		}

		// insert form data
        $id = $app->input->get($jb->fid,null);
		$item = $model->getData($id);
        $user = $app->getIdentity();
        // Admin Users can override user id
		$fuser=$jb->getSubdata('fuser');
		if (!empty($fuser)) {
			if (isset($item->{$fuser}) && !empty($item->{$fuser}) && $item->{$fuser}!=$user->id) {
				if (!$user->authorise('core.admin')) {
					throw new Exception(Text::_("JDB_ALERTNOTAUTH"),403);
				}
			} else {
				$item->{$fuser}=$user->id;
			}
		}

		if (FormHelper::saveData($jb,$item))
		{
			// send formdata to admin
			if (empty($id) && $params->get("infomail", 0) == 1)
			{
				$db->setQuery("SELECT email FROM `#__users` WHERE `id` =" . (int)$params->get("infomail_user") . " LIMIT 1");
				if ($email = $db->loadResult())
				{
					$MailFrom = $app->get('mailfrom');
					$FromName = $app->get('fromname');
					$subject  = "JooDatabase - " . Text::_("JDB_NEW_DATABASE_ENTRY") . " - " . $jb->name;
					$body     = $subject . "\r\n";
					$body     .= "Site: " . $app->get('sitename') . " - " . Uri::current() . "\r\n\r\n";
					$body     .= Text::_("JDB_RECIEVED_VALUES") . "\r\n===================\r\n";
					foreach ($item as $var => $val)
						if (!empty($val))
							$body .= ucfirst($var) . ": " . $val . "\r\n";
					$body .= "===================\r\n\r\n";
					$mail = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
					$mail->addRecipient($email);
					$mail->setSender(array($MailFrom, $FromName));
					$mail->setSubject($FromName . ': ' . $subject);
					$mail->setBody($body);
					try {
						$sent = $mail->Send();
					} catch (\Exception) {
						$app->enqueueMessage('Mail could not be sent. Please inform admin!', 'warning');
					}
				}

			}
			$this->getDatasetImage($jb,$item);
			FormHelper::saveSubData($jb,$item);

		}
		$this->setRedirect(Route::_('index.php?Itemid='.$Itemid,false));
	}

	/**
	 * Save database entry from edit form
	 */
	public function save()
	{
		// Check for request forgeries.
		$this->checkToken();

		$app = Factory::getApplication();
		$model = $this->getModel('edit');
		$jb = $model->getJoobase();
		$id = $app->input->get($jb->fid);
		$item = $model->getData($id);
		// insert form data
		$copy = ($this->task=='copy') ? true : false;
		if (FormHelper::saveData($jb,$item,$copy))
		{
			// Delete exiting image
			if ($app->input->getInt('delete_image',0) == 1)
			{
				$image = JPATH_ROOT . "/images/joodb/db" . $jb->id . "/img" . $id;
				@unlink($image . ".jpg");
				@unlink($image . "-thumb.jpg");
			}

			$this->getDatasetImage($jb,$item);
			FormHelper::saveSubData($jb,$item);
			$view = 'article';
		} else {
			$view = 'edit';
		}
		$this->setRedirect(Route::_('index.php?option=com_joodb&joobase=' . $jb->id . '&view='.$view.'&id=' . $item->{$jb->fid} . '&Itemid=' . $app->input->getInt('Itemid'), false));
	}

    /**
     * Delete an enty in the frontend
     */
    public function delete() {
	    $app = Factory::getApplication();
        $model = $this->getModel('edit');
        $jb = $model->getJoobase();
        $id = $app->input->get('id');
        $msg = Text::_("JDB_ERROR");
        if (!empty($id)) {
            $db = $jb->getTableDbo();
            $result = $db->setQuery("DELETE FROM `".$jb->table."` WHERE `".$jb->fid."` =".(int)$id)->execute();
            if (!empty($result)) $msg = Text::_("JDB_ITEM_DELETED");
        }
        $this->setRedirect(Route::_('index.php?option=com_joodb&joobase='.$jb->id.'&view=catalog&Itemid='.$app->input->getInt('Itemid'),false), $msg);
    }


	/**
	 * Print out a captcha image
	 */
	public function captcha()
	{
		$app = Factory::getApplication();
		JoodbHelper::printCaptcha();
		$app->close();
	}

	/**
	 * Add entries to notepad ...
	 */
	public function addToNotepad() {
		$app = Factory::getApplication();
  		$session = $app->getSession();
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
		$app = Factory::getApplication();
		$session = $app->getSession();
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
		$session = Factory::getApplication()->getSession();
		$session->set('articles', '');
		$this->display();
	}

	/**
	 * Wrapper for blob images and files
	 */
	public function getFileFromBlob() {
		$app = Factory::getApplication();
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
					header("Content-Disposition: ".$disp."; filename=".$field."-".OutputFilter::stringURLSafe($item->{$jb->ftitle}).".".$ext);
					header("Content-Length: ".mb_strlen($item->{$field}, '8bit'));
					echo $item->{$field};
				}
			}
		}
		$app->close();
	}

	/**
	 * Get anonymous registration info for validation
	 */
	public function getLicenseInfo() {
		$app = Factory::getApplication();
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$db->setQuery("SELECT value FROM `#__joodb_settings` WHERE `name` = 'license' AND `jb_id` IS NULL",0,1);
		header('Content-type: application/json');
		$status=json_decode($db->loadResult());
		echo '{"hash": "'.$status->hash.'"}';
		$app->close();
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
		$app = Factory::getApplication();
		// Attach and resize uploaded image
		$newimage = $app->input->files->get('joodb_dataset_image');
		if (!empty($newimage['name']))
		{
			$params = new Registry($jb->params);
			$destination = JPATH_ROOT . "/images/joodb/db" . $jb->id . "/img" . $item->{$jb->fid};
			FilesHelper::processUploadedImage($newimage, $destination, $params);
		}

	}

}
