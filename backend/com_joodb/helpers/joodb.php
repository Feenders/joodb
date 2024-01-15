<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

/**
 * Admin helper class ...
 */

class JoodbAdminHelper
{

	//  returns a list with all availiable functions
	public static function getFunctions($context) {
		$functions = array (
			"catalog" => array('loop','pagenav','pagecount','resultcount','nodata','limitbox','searchbox|[fieldlist]','searchfield|FIELD|[cond]','alphabox','checkbox','groupselect|FIELD|[size]','sortlink|FIELD','readon','printbutton','notepadbutton','exportbutton','searchbutton','resetbutton','backbutton','editbutton|[cond]','deletebutton'),
			"single" => array('printbutton','notepadbutton','backbutton','nextbutton','prevbutton','editbutton|[cond]'),
			"print" => array(),
			"form" => array('form|FIELD','submitbutton','captcha','imageupload','subforms'),
			"general" => array('ifis|FIELD|[value]|[cond]','ifnot|FIELD','else','endif','image','thumb','path2image','path2thumb','subtemplate|TEMPLATENAME','translate|STRING'));
		return array_merge($functions[$context],$functions['general']);
	}

	//  returns a optionlist with all availiable functions
	public static function printTemplateFooter($editorid,$fieldlist,$context) {
		echo "<table class='mt-3 paramlist admintable' style='width: 100%;'><tr><td class='paramlist_key'>".JText::_( 'Insert field' )."</td><td class='paramlist_value'>";
		echo '<select class="form-select" style="width:auto;" name="ifld_'.$editorid.'" onChange="jInsertEditorText(this.options[this.selectedIndex].value,\''.$editorid.'\');this.selectedIndex=0;"><option>...</option>\n';
		foreach ($fieldlist as $field) {
			echo "<option>{joodb field|".$field->Field."}</option>\n";
		}
		echo '</select>';
		echo "</td><td class='paramlist_key'>".JText::_( 'Insert function' )."</td><td class='paramlist_value'>";
		echo '<select class="form-select"  style="width:auto;"  name="ifunc_'.$editorid.'" onChange="jInsertEditorText(this.options[this.selectedIndex].value,\''.$editorid.'\');this.selectedIndex=0;"><option>...</option>\n';
		$flist = self::getFunctions($context);
		foreach ($flist as $f) {
			echo "<option>{joodb ".$f."}</option>\n";
		}
		echo '</select></td></tr></table>';
	}


	/**
	 * Get a list with Fields of a special type ...
	 * @param string $type
	 * @param object $fields
	 * @return array of fieldnames
	 */
	public static function selectFieldTypes($type, $fields) {
		$fselect = array();
		foreach ($fields as $fcell) {
			if ($type=="primary") {
				if (strtoupper($fcell->Key) == "PRI") $fselect[] = $fcell->Field;
			} else if ($type=="text") {
				if (strpos($fcell->Type,"text")!==false) $fselect[] = $fcell->Field;
			} else if ($type=="shorttext") {
				if (strpos($fcell->Type,"varchar")!==false) $fselect[] = $fcell->Field;
				if (strpos($fcell->Type,"text")!==false) $fselect[] = $fcell->Field;
			} else if ($type=="date") {
				if (strpos($fcell->Type,"date")!==false) $fselect[] = $fcell->Field;
				if (strpos($fcell->Type,"timestamp")!==false) $fselect[] = $fcell->Field;
			} else if ($type=="number") {
				if (strpos($fcell->Type,"int")!==false) $fselect[] = $fcell->Field;
			}
		}
		return $fselect;
	}

	/**
	 * Resize a image to smaller jpg ...
	 * @param string $source
	 * @param string $destination
	 * @param int $size_w
	 * @param int $size_h
	 * @param int $quality
	 * @param boolean $force_resize
	 * @param boolean $greyscale
	 */
	public static function resizeImage($source,$destination, $size_w=200, $size_h=200, $quality=80,$force_resize=false,$grayscale=false) {
		$imageinfo = getimagesize($source);
		$src_img = null;
		switch ($imageinfo[2]) {
			case 1:
				$src_img = imagecreatefromgif($source);
				break;
			case 2:
				$src_img = imagecreatefromjpeg($source);
				break;
			case 3:
				$src_img = imagecreatefrompng($source);
				break;
			case 15:
				$src_img = imagecreatefromwbmp($source);
				break;
			default:
				$src_img = false;
		}

		if ($src_img) {
			$src_width = $imageinfo[0];
			$src_height = $imageinfo[1];
			if($src_width>=$src_height) {
				$new_w = $size_w;
				$new_h = abs(($size_w/$src_width)*$src_height);
				if ($new_h>=$size_h) {
					$new_h = $size_h;
					$new_w = abs(($size_h/$src_height)*$src_width);
				}
			}
			else {
				$new_h = $size_h;
				$new_w = abs(($size_h/$src_height)*$src_width);
				if ($new_w>=$size_w) {
					$new_w = $size_w;
					$new_h = abs(($size_w/$src_width)*$src_height);
				}
			}
			// keep original size if file is to smal
			if (($src_height<=$size_h) && ($src_width<=$size_w) && ($force_resize==false)) {
				$new_h = $src_height;
				$new_w = $src_width;
			}
			$dst_img = imagecreatetruecolor($new_w,$new_h);
			// Creating the Canvas
			imagecopyresampled($dst_img,$src_img,0,0,0,0,$new_w,$new_h,$imageinfo[0],$imageinfo[1]);

			if ($grayscale) {
				$bwimage= imagecreate($new_w,$new_h);
				//Creates the 256 color palette
				for ($c=0;$c<256;$c++){ $palette[$c] = imagecolorallocate($bwimage,$c,$c,$c);}
				//Reads the origonal colors pixel by pixel
				for ($y=0;$y<$new_h;$y++) {
					for ($x=0;$x<$new_w;$x++) {
						$rgb = imagecolorat($dst_img,$x,$y);
						$r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
						//This is where we actually use yiq to modify our rbg values, and then convert them to our grayscale palette
						$gs = (($r*0.299)+($g*0.587)+($b*0.114));
						imagesetpixel($bwimage,$x,$y,$palette[$gs]);
					}
				}
				imagejpeg($bwimage, $destination, $quality);
			} else {
				imagejpeg($dst_img, $destination, $quality);
			}
			chmod($destination, 0665);
			return true;
		}
		return false;
	}


	/**
	 * Get Mime type from blob-data
	 * @param strinf $data
	 * @return string
	 */
	static function getMimeType(&$data)
	{
		//File signatures with their associated mime type
		$Types = array(
			"474946383761"=>"image/gif",                        //GIF87a type gif
			"474946383961"=>"image/gif",                        //GIF89a type gif
			"89504E470D0A1A0A"=>"image/png",
			"FFD8FFE0"=>"image/jpeg",                           //JFIF jpeg
			"FFD8FFE1"=>"image/jpeg",                           //EXIF jpeg
			"FFD8FFE8"=>"image/jpeg",                           //SPIFF jpeg
			"377ABCAF271C"=>"application/zip",                  //7-Zip zip file
			"504B0304"=>"application/zip",                      //PK Zip file ( could also match other file types like docx, jar, etc )
			"255044462D"=>"application/pdf",
			"D0CF11E0"=>" application/msword"
		);
		$sig = substr($data,0,60);
		$sig = unpack("H*",$sig);
		$sig = array_shift($sig); //String representation of the hex values
		foreach($Types as $MagicNumber => $Mime)
		{
			$MagicNumber = (string) $MagicNumber;
			if( stripos($sig,$MagicNumber) === 0 )
				return $Mime;
		}

		//Return octet-stream (binary content type) if no signature is found
		return "application/octet-stream";
	}

	/**
	 * Load required Javascript and styles
	 *
	 * @since 3.5
	 */
	public static function prepareDocument() {


		JHtml::_('stylesheet', 'com_joodb/icons.css', array('version' => 'auto', 'relative' => true));
		JHtml::_('stylesheet', 'com_joodb/joodb-admin.css', array('version' => 'auto', 'relative' => true));

		JHtml::_('jquery.framework');

		JHtml::_('script', 'com_joodb/joodb-admin.js', array('version' => 'auto', 'relative' => true));
		JHtml::_('script', 'com_joodb/featherlight.min.js', array('version' => 'auto', 'relative' => true));
		JHtml::_('stylesheet', 'com_joodb/featherlight.min.css', array('version' => 'auto', 'relative' => true));
	}


	/**
	 * Print a custom popup as squeezebox
	 * @param string $class the calls name
	 * @param string $text the button title
	 * @param string $url the url
	 * @param int $width the box width
	 * @param int $height the box height
	 */
	static function getPopupButton($class,$text,$url,$width=640,$height=400)
	{
		$bar = JToolBar::getInstance('toolbar');
		$addclass = ($class=="new") ? "btn-success" : "";
		$text = JText::_($text);
		$html = '<div class="btn-group">';
		$html  .= '<a class="fbmodal btn btn-small '.$addclass.'" title="'.$text.'" href="'.$url.'" data-width="'.$width.'px" data-height="'.$height.'px"><span class="icon-'.$class.'"></span>&nbsp;'.$text.'</a>';
		$html  .= '</div>';
		$bar->appendButton('Custom', $html, $text);
	}

}

