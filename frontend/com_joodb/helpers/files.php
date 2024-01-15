<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Component Helper
jimport('joomla.application.component.helper');

/**
 * JooDB Component Helper
 */
class JoodbFilesHelper
{

	/**
 	* Parse template for wildcards and return text
 	* @param array $image post image array
 	* @param string $destination
 	* @param array $params 
 	* @return string message/error if any
 	*
 	*/
	static function processUploadedImage($newimage,$destination,$params) {
		$app = JFactory::getApplication();
		$newimage['name'] = strtolower(JFilterOutput::cleanText($newimage['name']));
		$org_img = $destination."-original".strrchr($newimage['name'],".");
		// Move uploaded image
		jimport('joomla.filesystem.file');
		JFile::upload($newimage['tmp_name'], $org_img);
		if (file_exists($org_img)) {
			// make shure we accept only png, gif or jpg
			$ext = false;
			if ($imageinfo = getimagesize($org_img)) {
				switch ($imageinfo[2]) {
					case 1:
						$ext = ".gif";
						break;
					case 2:
						$ext = ".jpg";
						break;
					case 3:
						$ext = ".png";
						break;
				}
			}
			if ($ext!==false) {
				chmod($org_img, 0664);
				// normal image
				JoodbAdminHelper::resizeImage($org_img,$destination.".jpg",$params->get("img_width",480),$params->get("img_height",600));
				// thumbnail image
				JoodbAdminHelper::resizeImage($org_img,$destination."-thumb.jpg",$params->get("thumb_width",120),$params->get("thumb_height",200));
			} else {
				$app->enqueueMessage( JText::_('Invalid Fileformat')." : ".$$newimage['name'],'error');
				return false;
			}
		
		} else {
			$app->enqueueMessage( JText::_('Unable to store image')." : ".$destination,'error');
			return false;
		}
		return true;
	}
}
?>
