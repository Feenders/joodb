<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

namespace Feenders\Component\Joodb\Site\Helper;

defined('_JEXEC') or die();

use Feenders\Component\Joodb\Administrator\Helper\JoodbAdminHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Helper\CMSHelper;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use function defined;

/**
 * Upload images and Files :: Helper
 */
class FilesHelper extends CMSHelper {

	/**
 	* Parse template for wildcards and return text
 	* @param array $newimage post image array
 	* @param string $destination
 	* @param Registry $params
 	* @return string message/error if any
 	*
 	*/
	static function processUploadedImage($newimage,$destination,$params) {
		$app = Factory::getApplication();
		$newimage['name'] = strtolower(OutputFilter::cleanText($newimage['name']));
		$org_img = $destination."-original".strrchr($newimage['name'],".");
		// Move uploaded image
		File::upload($newimage['tmp_name'], $org_img);
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
				$app->enqueueMessage( Text::_("JDB_INVALID_FILEFORMAT")." : ".$newimage['name'],'error');
				return false;
			}
		
		} else {
			$app->enqueueMessage( Text::_("JDB_UNABLE_TO_STORE_IMAGE")." : ".$destination,'error');
			return false;
		}
		return true;
	}
}
