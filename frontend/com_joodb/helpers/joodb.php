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
use Joomla\CMS\Event as CmsEvent;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\DispatcherInterface;

/**
 * JooDB Component Helper
 */
class JoodbHelper {

	public static $outputStates = array(true);

	/**
	 * Parse template for wildcards and return text
	 *
	 * @access public
	 * @param JooDB-Objext with fieldnames, Array with template parts, Object with Item-Data
	 * @return String The parsed output
	 *
	 */
	public static function parseTemplate(&$joobase, &$parts, &$item,&$params = null) {
		$output = "";
		$app = JFactory::getApplication();
		$Itemid = $app->input->getInt('Itemid');
		// generate link to the item
		$falias=$joobase->getSubdata('falias');
		if (!empty($app->get('sef')) && !empty($falias) && !empty($item->{$falias})) {
			$slug = $item->{$falias};
		} else {
			$slug = $item->{$joobase->fid}.':'.JFilterOutput::stringURLSafe($item->{$joobase->ftitle});
		}
		$itemlink = JRoute::_('index.php?option=com_joodb&view=article&id='.$slug.'&Itemid='.$Itemid,false);
		$imgpart = "/images/joodb/db".$joobase->id."/img".$item->{$joobase->fid};
		$filter = new JFilterInput();
		$level = 0;
		self::$outputStates = array(true);
		// replace item content with wildcards
		foreach( $parts as &$part ) {
			$doOutput = self::GetOutputState($item, $part, $level);
			if ($doOutput) {
				// replace field command with 1st parameter
				if (!empty($part->function)) {
					switch ($part->function) {
						case "field" :
							$part->function = $part->parameter[0];
							array_shift($part->parameter);
							if (isset($item->{$part->function})) {
								$output .= self::replaceField($joobase, $part, $item->{$part->function}, $itemlink, $item->{$joobase->fid});
							}
							break;
						case "readon" :
							$output .= self::getReadmore($itemlink);
							break;
						case "path2item" :
							$output .= $itemlink;
							break;
						case "path2editform" :
							$output .= JRoute::_('index.php?option=com_joodb&view=edit&joobase=' . $joobase->id . '&id=' . $item->{$joobase->fid}, false);
							break;
						case "nextbutton" :
							$output .= self::getNavigationButton($joobase, "next");
							break;
						case "prevbutton" :
							$output .= self::getNavigationButton($joobase, "prev");
							break;
						case "loopclass" :
							if ($params) $output .= ($params->get("counter")%2==0) ? "odd" : "even";
							break;
						case "loopcounter" :
							if ($params) $output .= $params->get("counter")+1;
							break;
						case "notepadbutton" :
							$output .= self::getNotepadButton($item, $joobase);
							break;
						case "printbutton" :
							$output .= self::getPrintPopup($item, $joobase);
							break;
						case "editbutton" :
							$output .= self::getEditButton($item, $joobase, $part);
							break;
						case "deletebutton" :
							$output .= self::getDeleteButton($item, $joobase);
							break;
						case "backbutton" :
							$output .= self::getBackbutton($joobase);
							break;
						case "subtemplate" :
							$output .= SubtemplateHelper::getSubTemplate($part->parameter[0], $joobase, $item);
							break;
						case "translate" :
							$output .= JText::_(addslashes($part->parameter[0]));
							break;
						case "image" :
							$image = JUri::root(true) . (file_exists(JPATH_ROOT . $imgpart . ".jpg") ? $imgpart . ".jpg" : "/media/com_joodb/images/no_image.png");
							$output .= '<img src="' . $image . '" alt="image" class="database-image';
							if (!file_exists(JPATH_ROOT . $imgpart . ".jpg")) $output .= " nopic";
							$output .= '" />';
							break;
						case "thumb" :
							$thumb = JUri::root(true) . (file_exists(JPATH_ROOT . $imgpart . "-thumb.jpg") ? $imgpart . "-thumb.jpg" : "/media/com_joodb/images/no_image-thumb.png");
							$image = JUri::root(true) . (file_exists(JPATH_ROOT . $imgpart . ".jpg") ? $imgpart . ".jpg" : "/media/com_joodb/images/no_image.png");
							$output .= '<a href="' . $image . '"  data-featherlight="image"><img src="' . $thumb . '" alt="thumb" class="database-thumb';
							if (!file_exists(JPATH_ROOT . $imgpart . ".jpg")) $output .= " nopic";
							$output .= '" /></a>';
							break;
						case "path2image" :
							$output .= JUri::root(true) . (file_exists(JPATH_ROOT . $imgpart . ".jpg") ? $imgpart . ".jpg" : "/media/com_joodb/images/no_image.png");
							break;
						case "path2thumb" :
							$output .= JUri::root(true) . (file_exists(JPATH_ROOT . $imgpart . "-thumb.jpg") ? $imgpart . "-thumb.jpg" : "/media/com_joodb/images/no_image.png");
							break;
						case "checkbox" :
							$ids = $app->input->get('cid', array(), '', 'array');
							$checked = (in_array($item->{$joobase->fid}, $ids)) ? 'checked="checked"' : '';
							$output .= '<input class="form-control check" type="checkbox" id="cb' . $item->{$joobase->fid} . '" name="cid[]" value="' . $item->{$joobase->fid} . '" ' . $checked . ' />';
							break;
						default:
							if (isset($joobase->fields[$part->function])) {
								/** @deprecated replace exisiting fields */
								$output .= (string) self::replaceField($joobase, $part, $item->{$part->function}, $itemlink, $item->{$joobase->fid});
							} else { // plugin system find commandfile
								$plugin = JPATH_COMPONENT . "/plugins/" . $filter->clean($part->function, "cmd") . ".php";
								if (file_exists($plugin)) include $plugin;
							}
					}
				}
				$output .= $part->text;
			}
		}
		return $output;
	}

	/**
	 * Replaces a joodb fieldname with field contennt
	 *
	 * @access public
	 * @param JooDB-Object with fieldnames, Part-object from the template, Text with field content
	 * @return The parsed output
	 *
	 */
	public static function replaceField(&$joobase, &$part, $field, $itemlink="",$id=0) {
		$app = JFactory::getApplication();
		$params	= $app->getParams();
		$fieldname = $part->function;
		if (!empty($itemlink) && $fieldname==$joobase->ftitle && $params->get('link_titles','0')!='0') {
			$itemlink = JFilterOutput::ampReplace(htmlspecialchars($itemlink, ENT_COMPAT, 'UTF-8', false));
			$att = array();
			$att['title'] = JText::_('Read more...');
			$att['class'] = "joodb_titlelink";
			$field= JHtml::_('link', $itemlink,$field, $att);
		}
		$ftparse = preg_split("/\(/",$joobase->fields[$part->function]);
		$function =  strtolower($ftparse[0]);
		$vars = (!empty($ftparse[1])) ? preg_split("/,/",str_replace(array(")","'"),"",$ftparse[1])) : null;
		// convert some of the fieldtypes
		if (!empty($field)) {
			switch($function) {
				case "varchar":
				case "tinytext":
				case "text":
				case "mediumtext":
				case "longtext":
					if (($function=="varchar" || $function=="tinytext") && $params->get('link_urls','0')!='0') {
						// try to detect and link urls ans emails
						if (preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $field)) {
							$field= JHtml::_('email.cloak', $field);
						} else if (strtolower(substr($field,0,4))=="www." || preg_match('#^http(s)?://#',$field)) {
							$ltext = preg_replace( "#^[^:/.]*[:/]+#i", "", $field);
							$link = JFilterOutput::ampReplace(htmlspecialchars($field, ENT_COMPAT, 'UTF-8', false));
							if (strtolower(substr($field,0,4))=="www.") {
								$link = "//".$link;
							}
							$field= JHtml::_('link', $link,$ltext, array("target"=>"_blank"));
						}
					}
					// shorten a text for abscracts
					if ((!empty($part->parameter[0])) && ($part->parameter[0]>1)) {
						$field = self::wrapText($field,$part->parameter[0]);
					}
					break;
				case "date":
					$field= JHtml::_('date', $field, JText::_('DATE_FORMAT_LC3'));
					break;
				case "datetime":
					$field= JHtml::_('date', $field, JText::_('DATE_FORMAT_LC2'));
					break;
				case "timestamp":
					$field= JHtml::_('date', $field, JText::_('DATE_FORMAT_LC2'));
					break;
				case "time":
					$field= substr($field,0,5);
					break;
				case "float" :
					if (isset($part->parameter[0])) {
						$field= number_format($field, (int)$part->parameter[0], JText::_('DECIMALS_SEPARATOR'), JText::_('THOUSANDS_SEPARATOR'));
					}
					break;
				case "decimal" :
					$field= number_format($field, $vars[1], JText::_('DECIMALS_SEPARATOR'), JText::_('THOUSANDS_SEPARATOR'));
					break;
				case "set":
					$set = preg_split("/,/",$field);
					if (!empty($set) && count($set)>=1) {
						$field = '<ul class="joodb_setlist">';
						foreach ($set AS $value) {
							$field .= '<li>'.$value.'</li>';
						}
						$field .= '</ul>';
					}
					break;
				case "tinyblob" :
				case "mediumblob" :
				case "blob" :
				case "longblob" :
					// @todo extend the field mode settings for downloads and so on to define filenames and scaling
					$field = (!empty($field) && !empty($id)) ? JURI::root().'index.php?option=com_joodb&task=getFileFromBlob&joobase='.$joobase->id.'&id='.$id.'&field='.$fieldname : "";
					break;
			}
		}
		return $field;
	}


	/**
	 * Split a template into parts return a array of of objects
	 *
	 * @access public
	 * @param String with template text
	 */
	public static function splitTemplate($template) {
		$psplit = preg_split('/\{joodb /U', $template);
		$parts=array();
		// insert text only for the first part
		if (substr($template,0,6)!="{joodb") {
			$e = new joodbPart();
			$e->text = array_shift($psplit);
			$parts[] =$e;
		}
		foreach ($psplit as $part) {
			$e = new joodbPart();
			$p=strpos($part,"}");
			if ($p===false) {
				$e->text=$part;
			} else {
				$e->text=substr($part,$p+1);
				$e->pval = trim(substr($part,0,$p));
				$e->parameter = preg_split("/\|/",$e->pval);
				$e->function = array_shift($e->parameter);
			}
			$parts[] =$e;
		}
		return $parts;
	}

	/**
	 * Calculates the outputsituation from condition arguments
	 *
	 * @access public
	 * @param misc $item
	 * @param misc $part
	 * @param bool $state
	 */
	public static function getOutputState(&$item, $part,&$level) {
		$val = (empty($part->parameter[0]) || !isset($item->{$part->parameter[0]})) ? "0" : (string) $item->{$part->parameter[0]};
		$state = (isset(self::$outputStates[$level])) ? self::$outputStates[$level] : true;
		if ($part->function=="ifis" || $part->function=="ifnot") $level++;
		if ($part->function=="ifis" && $state) { // check if field condition is true
			$part->pval = str_replace(array(' and ',' or '),array(' && ',' || '),$part->pval);
			$params = preg_split("/&&|\|\|/",$part->pval);
			// TODO: recursive complex analyse
			if (count($params)>1) {
				preg_match_all("/&&|\|\|/",$part->pval,$cond);
				$cond = $cond[0];
				foreach ($params AS $n => $p) {
					$params[$n] = preg_split("/\|/",trim($p));
				}
				array_shift($params[0]);
				foreach ($params AS $n => $p) {
					$val = (empty($p[0]) || !isset($item->{$p[0]})) ? "0" : (string) $item->{$p[0]};
					$params[$n]['state'] = self::evalConditions($p,$val,$state);
				}

				foreach ($cond AS $n => $c) {
					if ($c=="&&") {
						$state = ($params[$n]['state'] && $params[$n+1]['state']);
					} else {
						$state = ($params[$n]['state'] || $params[$n+1]['state']);
					}
					$params[$n+1]['state'] = $state;
					$params[$n]['state'] = $state;
				}
			} else {
				$state = self::evalConditions($part->parameter,$val,$state);
			}
		} else if ($part->function=="ifnot" && $state) { // check if field condition is false
			if (isset($part->parameter[1])) {
				$state = ($val!=$part->parameter[1]) ? true : false;
			} else {
				$state = (empty($val)) ? true : false;
			}
		} else if ($part->function=="else") {	$state = !$state;
		} else if ($part->function=="endif") {	$level--; if (self::$outputStates[$level]) $state = true; }

		self::$outputStates[$level] = $state;
		return ($state);
	}

	/**
	 * Check and or conditions
	 *
	 * @param $p
	 * @param $v
	 * @param $state
	 *
	 * @return bool
	 */
	protected static function evalConditions($p,$v,$state) {
		if (isset($p[1])) {
			$cond = (isset($p[2])) ? strtolower($p[2]) : "eq";
			switch ($cond) {
				case "le":
					$state = ($v<=$p[1]) ? true : false;
					break;
				case "lt":
					$state = ($v<$p[1]) ? true : false;
					break;
				case "gt":
					$state = ($v>$p[1]) ? true : false;
					break;
				case "ge":
					$state = ($v>=$p[1]) ? true : false;
					break;
				case "ne":
					$state = ($v!=$p[1]) ? true : false;
					break;
				default:
					$state = ($v==$p[1]) ? true : false;
					break;
			}
		} else {
			if ($state) $state = (!empty($v)) ? true : false;
		}
		return $state;
	}


	/**
	 * Returns popup link for printview as Icon or Text
	 *
	 * @access public
	 * @param Item, Params
	 */
	public static function getPrintPopup(&$item, &$joobase, $attribs = array())
	{
		$app = JFactory::getApplication();
		$itemid = $app->input->getInt('Itemid');
		$params	= JComponentHelper::getParams('com_joodb');
		$url  = 'index.php?option=com_joodb&view=article&joobase='.$joobase->id.'&id='.$item->{$joobase->fid}.'&Itemid='.$itemid.'&layout=print&tmpl=component&print=1';
		$status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=720,height=560,directories=no,location=no';

		// checks template image directory for image, if non found default are loaded
		if ( $params->get( 'show_icons', '0' )==1) {
			$text = JHtml::image('media/com_joodb/images/print.png', JText::_( 'Print' ) );
		} else {
			$text = '<span class="jicon jicon-print"></span>&nbsp;'. JText::_( 'Print' );
		}

		$attribs['title']	= JText::_( 'Print' );
		$attribs['onclick'] = "window.open(this.href,'win2','".$status."'); return false;";
		$attribs['rel']     = 'nofollow';

		return JHtml::link(JRoute::_($url), $text, $attribs);
	}

	/**
	 * Returns a add to notepad or remove from notepad link
	 *
	 * @access public
	 * @param object Item
	 * @param object joobase
	 * @return string
	 */
	public static function getNotepadButton(&$item, &$joobase)
	{
		$input = JFactory::getApplication()->input;

		if ($input->get('tmpl')!='component') {

			$params	= JComponentHelper::getParams('com_joodb');

			$task = ($input->get('layout')=="notepad") ? 'removeFromNotepad'  : 'addToNotepad';
			$url  = 'index.php?option=com_joodb&view=catalog&layout=notepad&joobase='.$joobase->id.'&task='.$task.'&article='.$item->{$joobase->fid};
			$urltext = ($input->get('layout')=="notepad") ?  JText::_('Remove from Notepad') : JText::_('Add to Notepad');
			// checks template image directory for image, if non found default are loaded
			if ( $params->get( 'show_icons', '0')==1) {
				$icon = ($input->get('layout')=="notepad") ? "remove.png" : "add.png";
				$text = JHtml::image('media/com_joodb/images/'.$icon,$urltext );
			} else {
				$icon = ($input->get('layout')=="notepad") ? "trash" : "clipboard";
				$text = '<span class="jicon jicon-'.$icon.'"></span>&nbsp;'.$urltext;
			}
			$attribs= array('title'=> $urltext);
			return JHtml::link(JRoute::_($url), $text,$attribs);
		}
	}


	/**
	 * Returns an icon or text link to edit item in frontend
	 *
	 * @access public
	 * @param object Item
	 * @param object joobase
	 * @return string
	 */
	public static function getEditButton(&$item, &$joobase,&$part)
	{
		$app = JFactory::getApplication();
		if ($app->input->get('tmpl')!='component') {
			$params	= JComponentHelper::getParams('com_joodb');
			$view = ((isset($part->parameter[0])) && (self::parameterToBoolean($part->parameter[0]))) ? "edit" : "form";
			$url  = JRoute::_("index.php?option=com_joodb&view=".$view."&joobase=".$joobase->id."&id=".$item->{$joobase->fid}."&Itemid=".$app->input->getInt('Itemid'));

			if (!self::checkAuthorization($joobase,"accesse",$item)) return;

			// checks template image directory for image, if non found default are loaded
			if ($params->get( 'show_icons', '0' )==1) {
				$icon = "edit.png";
				$text = JHtml::image('media/com_joodb/images/'.$icon,JText::_("EDIT_DATABASE_ENTRY"));
			} else {
				$text = '<span class="jicon jicon-edit"></span>&nbsp;'.JText::_("EDIT_DATABASE_ENTRY");
			}
			$attribs= array('title'=> JText::_("EDIT_DATABASE_ENTRY"));
			return JHtml::link(JRoute::_($url), $text,$attribs);
		}
	}


	/**
	 * Returns an icon to delete items in frontend
	 *
	 * @access public
	 * @param object Item
	 * @param object joobase
	 * @return string
	 */
	public static function getDeleteButton(&$item, &$joobase) {
		if (!self::checkAuthorization($joobase,"accesse",$item)) return;
		$app = JFactory::getApplication();
		$url  = JRoute::_("index.php?option=com_joodb&view=edit&joobase=".$joobase->id."&id=".$item->{$joobase->fid}."&task=delete&Itemid=".$app->input->getInt('Itemid'));
		$params	= JComponentHelper::getParams('com_joodb');
		if ($params->get('show_icons','0')==1) {
			$text = JHtml::image('media/com_joodb/images/remove.png',JText::_("DELETE DATABASE ENTRY"));
		} else {
			$text = '<span class="jicon jicon-trash"></span>&nbsp;'.JText::_("DELETE");
		}
		$attribs= array('title'=> JText::_("DELETE DATABASE ENTRY"),'onclick'=>'return confirm(\''.JText::_("REALLY DELETE").'\');');
		return JHtml::link(JRoute::_($url), $text,$attribs);
	}


	/**
	 * Returns a back to prev page link
	 *
	 * @access public
	 */
	public static function getBackbutton($joobase) {
		$app = JFactory::getApplication();
		$url  = JRoute::_("index.php?option=com_joodb&view=catalog&joobase=".$joobase->id."&Itemid=".$app->input->getInt('Itemid'));
		$params	= JComponentHelper::getParams('com_joodb');
		if ($params->get('show_icons','0')==1) {
			$text = JHtml::image('media/com_joodb/images/back.png', JText::_('BACK') );
		} else {
			$text = '<span class="jicon jicon-left-open"></span>&nbsp;'.JText::_('BACK');
		}
		return JHtml::link($url, $text,array('title'=>  JText::_('BACK'),'class'=>'backbutton'));
	}

	/**
	 * Returns a back to prev page link
	 *
	 * @access public
	 * @param string link
	 */
	public static function getReadmore($url) {
		$params	= JComponentHelper::getParams('com_joodb');
		if ($params->get('show_icons','0')==1) {
			$text = JHtml::image('media/com_joodb/images/next.png',JText::_('READ MORE...'));
		} else {
			$text = JText::_('READ MORE...').'&nbsp;<span class="jicon jicon-right-open"></span>';
		}
		return  JHtml::link($url , $text,array('title'=>  JText::_('READ MORE...'),'class'=>'readonbutton'));
	}


	/**
	 * Returns a next or previous Item link
	 *
	 * @access public
	 * @param string next or prev
	 * @return string
	 */
	public static function getNavigationButton(&$joobase,$side="next") {
		$app = JFactory::getApplication();
		require_once JPATH_BASE.'/components/com_joodb/models/catalog.php';
		$model = new JoodbModelCatalog();
		if (!$item=$model->getSideElementUrl($side)) return;
		$falias=$joobase->getSubdata('falias');
		if (!empty($app->get('sef')) && !empty($falias) && !empty($item->{$falias})) {
			$slug = $item->{$falias};
		} else {
			$slug = $item->{$joobase->fid}.':'.JFilterOutput::stringURLSafe($item->{$joobase->ftitle});
		}
		$url = JRoute::_('index.php?option=com_joodb&view=article&joobase='.$joobase->id.'&id='.$slug."&position=".$item->jb_pos."&total=".$item->jb_total,false);
		if ($side=="next") {
			$btext = JText::_('NEXT_ENTRY').'&nbsp;<span class="jicon jicon-right-open"></span>';
			$image = "next.png";
		} else {
			$btext = '<span class="jicon jicon-left-open"></span>&nbsp;'.JText::_('PREVIOUS_ENTRY');
			$image = "back.png";
		}
		$params	= JComponentHelper::getParams('com_joodb');
		if ($params->get('show_icons','0')==1) {
			$text = JHtml::image('media/com_joodb/images/'.$image, strip_tags($btext) );
		} else {
			$text = $btext;
		}
		return JHtml::link($url,$text,array('title'=>  strip_tags($btext),'class'=>$side.'button'));
	}


	/**
	 * Returns Search box for catalog view
	 *
	 * @access public
	 * @param  current Searchstring, Joobase
	 */
	public static function getSearchbox($search="",$parameter = array())
	{
		$app = JFactory::getApplication();
		$stext = JText::_('Search...');
		$sval = ($search!="") ? $search : $stext;
		$searchform =  '<div class="searchbox"><input class="form-control searchword" type="text"'
		               .' onfocus="if(this.value==\''.$stext.'\') this.value=\'\';" onblur="if(this.value==\'\') this.value=\''.$stext.'\';" '
		               .' value="'.htmlspecialchars(stripcslashes($sval), ENT_QUOTES, "UTF-8").'" size="20" alt="'.$stext.'" maxlength="40" name="search" />';
		if (!empty($parameter[0])) {
			$fields = @preg_split("/,/",$parameter[0]);
			$searchform .= "<select class='form-select' name='searchfield'><option value=''>".JText::_('All fields')."</option>" ;
			$rf = $app->input->get("searchfield");
			foreach ($fields as $field) {
				$field=trim($field);
				$searchform .= "<option value='".$field."' " ;
				if ($rf==$field) $searchform .= "selected";
				$searchform .= ">".ucfirst(str_replace(array("-","_")," ",$field))."</option>" ;
			}
			$searchform .= "</select>" ;
		}
		$searchform .=  "<button class='btn btn-primary btn-search' type='submit' ><span class='jicon-search'></span>&nbsp;".$stext."</button>"
		                ."<button class='btn btn-dark btn-reset' type='button' onclick='submitSearch(\"reset\");void(0);' ><span class='jicon-cancel'></span>&nbsp;".JText::_('Reset...')."</button></div>";
		return $searchform;
	}

	/**
	 * Returns a select-box of possible row to search for
	 * @access public
	 * @param  current Joobase, parameters, values
	 */
	public static function getGroupselect(&$joobase,$parameter,$values)
	{
		$app = JFactory::getApplication();
		$gs =  $app->getUserStateFromRequest("com_joodb".$joobase->id.'.gs', 'gs',array(), 'array');
		$sv = (isset($gs[$parameter[0]])) ? $gs[$parameter[0]] : array();
		$size = (isset($parameter[1]) && $parameter[1]>1) ? 'size="'.$parameter[1].'" multiple="multiple" ' : "";
		$searchform = '<select class="form-select groupselect" id="gs_'.$parameter[0].'" name="gs['.$parameter[0].'][]" '.$size.' >' ;
		$searchform .= '<option value="">...</option>';
		if ($values)
			foreach ($values as $value) {
				$selected = (array_search($value->delimeter.$value->value.$value->delimeter, $sv)!==false) ? 'selected="selected"' : '';
				if (!empty($value->value))
					$searchform .= '<option value="'.$value->delimeter.$value->value.$value->delimeter.'" '.$selected.'>'.$value->value.' ('.$value->count.')</option>';
			}
		$searchform .= "</select>";
		return $searchform;
	}


	/**
	 * Returns a roman alphabet to select the first letters ot the title
	 *
	 * @access public
	 * @param current Alphachar
	 */
	public static function getAlphabox($alphachar, &$joobase)
	{
		$alphabox = "<div class='pagination alphabox'><ul>";
		$alphabet= array ('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		foreach ($alphabet as $achar) {
			if ($achar==$alphachar) {
				$alphabox .= "<li class='active'><span>".ucfirst($achar)."</span></li>";
			} else {
				$alphabox .= "<li><a href='Javascript:setAlpha(\"".$achar."\");void(0);'>".ucfirst($achar)."</a></li>";
			}
		}
		$alphabox .=  "<li><a class='reset' href='Javascript:submitSearch(\"reset\");void(0);'>&raquo;".JText::_('All')."</a></li></ul></div>";
		return $alphabox;
	}

	/**
	 * Get complete link or only url to sort
	 *
	 * @access public
	 * @params fieldname fpr sort, [linktext]
	 */
	public static function getOrderlink(&$parameter,&$joobase)
	{
		$app = JFactory::getApplication();
		$params	= $app->getParams();
		$ordering = "ASC"; $orderclass = "";
		if ($app->getUserStateFromRequest('com_joodb.orderby', 'orderby', $params->get('orderby','fid'), 'string')==$parameter[0]) {
			$ordering = (strtolower($app->input->getCmd('ordering')) == "asc") ? "DESC" : "ASC";
			$orderclass = (strtolower($app->input->getCmd('ordering')) == "asc") ? "-down" : "-up";
		}
		$href = self::_findItem($joobase,'&orderby='.$parameter[0].'&ordering='.$ordering);
		if (count($parameter)>1) {
			$url = '<a href="'.$href.'" data-orderby="'.$parameter[0].'" data-ordering="'.$ordering.'" ><span class="jicon jicon-sort'.$orderclass.'"></span>&nbsp;'.$parameter[1]."</a>";
		}
		return $url;
	}


	/**
	 * Returns a captcha box
	 *
	 * @access public
	 *
	 */
	public static function getCaptcha(){
		$captcha ="<div class='joocaptcha' style='margin: 5px 0;' >"
		          ."<img src='".Juri::root(false)."index.php?option=com_joodb&task=captcha&".microtime(true)."' alt='captcha' style='width:200px; height:50px; margin: 5px 0; border: 1px solid black;'  />"
		          ."<br><input class='form-control required' name='joocaptcha' id='joocaptcha' style='width:190px;' size='1' maxlength='5' /></div>";
		return $captcha;
	}

	/**
	 * Output of a captcha image
	 *
	 * @access public
	 *
	 */
	public static function printCaptcha(){

		header("Content-Type: image/png");

		// Generate code for Captcha
		$code = "";
		$codelength = 5;
		$pool = "qwertzupasdfghkyxcvbnm23456789";
		srand ((double)microtime()*1000000);
		for($n = 0; $n < $codelength; $n++) {
			$code .= substr($pool,(rand()%(strlen ($pool))), 1);
		}

		$includepath=JPATH_ROOT."/media/com_joodb/images/";
		$fontsize=20;
		// Get the size
		$bbox = imagettfbbox($fontsize, 0, $includepath."captcha.ttf", $code);

		// calculate size of the image
		$x= $bbox[2]+(2*$bbox[3]);
		$y= (-$bbox[7])+(2*$bbox[3]);
		$background = imagecreatefromjpeg($includepath."captcha.jpg");

		//prepare the image
		$im  =  ImageCreateTrueColor ( 200,  50 );
		$fill = ImageColorAllocate ( $im ,  0,  0, 0 );
		$color = ImageColorAllocate ( $im , 235  , 235, 235 );

		imagecopy($im,$background,0,0,rand(0,600),rand(0,500),200,50);
		$startx = rand(5,110); $starty = rand(25,40);

		// rotate and shift each char randomly
		for($i=0; $i<$codelength; $i++) {
			$ch = $code[$i];
			ImageTTFText ($im, $fontsize, rand(-10,10) , $startx + (15*$i) , $starty , $color, $includepath."captcha.ttf", $ch);
		}

		ImagePNG ( $im );
		ImageDestroy ($im);

		// store the code to the session
		$session = JFactory::getSession();
		$session->set('joocaptcha',$code);

	}

	/**
	 * Output text... trigger content event before ...
	 * @param object $text
	 * @param array $params
	 * @param sting view name of the view
	 */
	public static function printOutput(&$page, &$params,$view="article") {
		PluginHelper::importPlugin('content');
		Factory::getApplication()->triggerEvent('onContentPrepare', array ('com_joodb.'.$view, &$page, &$params,0));
		Factory::getApplication()->triggerEvent('onContentBeforeDisplay', array ('com_joodb.'.$view, &$page, &$params,0));
		echo $page->text;
		Factory::getApplication()->triggerEvent('onContentAfterDisplay', array ('com_joodb.'.$view, &$page, &$params,0));
	}


	/**
	 * Try to find menuitem for the database
	 *
	 * @access private
	 * @param id of the referring database
	 */
	public static function _findItem(&$joobase,$params="")
	{
		$Itemid = JFactory::getApplication()->input->getInt('Itemid');
		return JRoute::_("index.php?option=com_joodb&view=catalog&joobase=".$joobase->id."&Itemid=".$Itemid.$params,false);
	}

	/**
	 * Check the Authorization ...
	 * @todo Joomla ACL compatibilty
	 * @param jobase misc
	 * @param string $section
	 * @param item misc
	 * @return boolean
	 */
	public static function checkAuthorization(&$joobase, $section="accessd",&$item=null) {
		$jparams = new JRegistry($joobase->params);
		$user = JFactory::getUser();
		$fuser= $joobase->getSubdata('fuser');
		$levels	= $user->getAuthorisedViewLevels();
		$levels = array_flip($levels);
		$has_access = false;

		// editfunctions with special needs
		if ($section=="accesse") {
			if (empty($user->id)) return false;
			if ($jparams->get("accesse","0")==1) {
				if ($user->authorise('core.admin')) {
					$has_access = true;
				} else {
					if (array_key_exists($jparams->get("accessf","2"),$levels)) {
						if (!empty($item) && !empty($fuser)) {
							if ($item->{$fuser}==$user->id) $has_access = true;
						} else {
							$has_access = true;
						}
					}
				}
			}
		} else {
			$level_need = $jparams->get( $section, 1 );
			if ( empty( $level_need ) )
			{
				$level_need = 1;
			}
			if ( array_key_exists( $level_need, $levels ) )
			{
				$has_access = true;
			}
			else
			{
				if (! empty( $item->{$joobase->fid} ) && ! empty( $fuser ) )
				{
					if ( $item->{$fuser} == $user->id )
					{
						$has_access = true;
					}
				}
			}

			if (!$has_access) {
				// redirect
				$uri = JUri::getInstance();
				$return	= $uri->toString();
				$url  = JRoute::_('index.php?option=com_users&view=login&return=');
				$url .= base64_encode($return);
				$app = JFactory::getApplication();
				$app->redirect($url, JText::_('Please login') );
				$app->close();
			}
		}
		return $has_access;
	}


	/**
	 * Load required Javascript and styles
	 *
	 * @since 3.5
	 */
	public static function prepareDocument() {

		JHtml::_('jquery.framework');

		$app = JFactory::getApplication();
		JHtml::_('stylesheet', 'com_joodb/icons.css', array('version' => 'auto', 'relative' => true));
		if (file_exists(JPATH_BASE."/templates/".$app->getTemplate()."/css/joodb.css")) {
			JHtml::_('stylesheet', "templates/".$app->getTemplate()."/css/joodb.css");
		} else {
			JHtml::_('stylesheet', 'com_joodb/joodb.css', array('version' => 'auto', 'relative' => true));
		}

		JHtml::_('script', 'com_joodb/featherlight.min.js', array('version' => 'auto', 'relative' => true));
		JHtml::_('stylesheet', 'com_joodb/featherlight.min.css', array('version' => 'auto', 'relative' => true));
	}

	/**
	 * Shorten a text
	 *
	 * @access public
	 * @param String with text
	 * @param Integer with maximum length
	 * @return Truncated text
	 *
	 */
	public static function wrapText($text,$maxlen=120) {
		$text = strip_tags($text);
		if (strlen($text)>$maxlen) {
			$len = strpos($text," ",$maxlen);
			if ($len) $text = substr($text,0,$len).' &hellip;';
		}
		return $text;
	}

	/**
	 * Returns the condition of a boolean string parameter
	 * @param string $value
	 * @return boolean
	 */
	public static function parameterToBoolean($value) {
		if (empty($value)) return false;
		$def = array('true','on','1','yes');
		$value = trim(strtolower((string)$value));
		return (array_search($value,$def)===false) ? false : true;
	}

}

// a pure object class to keep parts
class joodbPart {
	//the joodb function
	var $function = false;
	//an array of parameters
	var $parameter = array();
	// the text to the next comand
	var $text = "";
    // Parameter Values
    var $pval = null;
}
