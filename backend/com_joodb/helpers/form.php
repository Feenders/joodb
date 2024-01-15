<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

/**
 * Helper class for related fields and tables
 */

use Joomla\String\StringHelper;

class JoodbFormHelper
{

	private static $editor = null;

	/**
	 * Output of a form field regarding related tables and field type
	 * @param $joobase
	 * @param $item
	 * @param $field
	 * @param $param
	 * @return string
	 */
	public static function getFormField(&$joobase,&$item,&$field,$param=null) {
		$app = JFactory::getApplication();
		$result= "";
		$name = $field->Field;
		$subitems = $joobase->getSubitems();
		// create a set of fields to replace
		$ss_array = array();
		foreach ($subitems AS $subitem)
			if ($subitem->type=="4" )
				if (!isset($ss_array[$subitem->idx_sub]))
					$ss_array[$subitem->idx_sub] = $subitem;
		$typearr = preg_split("/\(/",$field->Type);
		$fid = "jform_".preg_replace("/[^A-Z0-9]/i","",$name);
		if (!isset($item->{$name}) || empty($item->{$name}))  {
			$data = $app->getUserState('com_joodb.form.data');
			$item->{$name} = (isset($data[$name])) ? $data[$name] : '';
		}
		$typevals = array("");
		$required = ($field->Null=="NO"  || $name==$joobase->ftitle) ? 'required" required="required' :"";
		if (isset($typearr[1])) { $typevals =  preg_split("/','/",trim($typearr[1],"')"));	}
		// get default value
		if (empty($item->{$joobase->fid}) && ($field->Default!=NULL)) { $item->{$name} = $field->Default; }
		if ($field->Extra=='auto_increment') {
			$result .= '<input class="form-control input-small" type="text" name="'.$name.'" id="'.$fid.'" value="'.htmlspecialchars($item->{$name}, ENT_COMPAT, 'UTF-8').'" size="40" disabled />';
		} else
			switch ($typearr[0]) {
				case 'varchar' :
				case 'char' :
				case 'tinytext' :
					$type = "text";
					if ($param=="email") {
						$type = "email";
						$required = "validate-email ".$required;
					}
					if ($param=="password") {
						$type = "password";
					}
					$result .= '<input class="form-control input-'.(($typevals[0]<30) ? 'large' : 'xxlarge')." ".$required.'" type="'.$type.'" name="'.$name.'" id="'.$fid.'" value="'.htmlspecialchars($item->{$name}, ENT_COMPAT, 'UTF-8').'" maxlength="'.$typevals[0].'" size="60" />';
					break;
				case 'int' :
				case 'smallint' :
				case 'mediumint' :
				case 'bigint' :
				case 'decimal' :
				case 'float' :
				case 'double' :
				case 'real' :
					if ($name==$joobase->getSubdata('fuser')) {
						if (JFactory::getUser()->authorise('core.manage', 'com_users'))
						{
							$disabled = ($app->getName() == "administrator") ? '"' : '" readonly="true"';
							$ua       = simplexml_load_string('<field name="' . $name . '" type="user" label="User" class="form-control ' . $required. $disabled . ' filter="unset" />');
							$form = new JForm('user');
							$uf       = new JFormFieldUser();
							$uf->setForm($form);
							$uf->setup($ua, (int) $item->{$name});
							$result .= $uf->renderField(array("hiddenLabel" => true));
						}
						else
						{
							$result .= '<input class="form-control input-medium ' . $required . '" type="text" name="' . $name . '" id="' . $fid . '" disabled="disabled" value="' . $item->{$name} . '" />';
						}
					} else {
						$subitem=$joobase->getSubFormField($name);
						if (!empty($subitem)) {
							$result .= self::getSubitemSelect($joobase,$subitem,$item->{$name},$required);
						} else {
							$result .= '<input class="form-control input-medium '.$required.'" type="text" name="'.$name.'" id="'.$fid.'" value="'.$item->{$name}.'" />';
						}
					}
					break;
				case 'tinyint' :
					if ((!empty($joobase->fstate) && $joobase->fstate==$name) || ($param=="yesno")) {
						$result .=  '<select class="form-select input-small" name="'.$name.'" id="'.$fid.'"><option value="0">'.JText::_('JNo').'</option><option value="1" ';
						if (!empty($item->{$name})) $result .= 'selected="selected"';
						$result .= '>'.JText::_('JYes').'</option></select>';
					} else {
						$result .= '<input class="form-control input-mini ' . $required . '" type="text" name="' . $name . '" value="' . ( (int) $item->{$name} ) . '" maxlength="4" size="4" />';
					}
					break;
				case 'datetime' :
				case 'timestamp' :
					$item->{$name} = preg_replace("/[^0-9:\- ]/","",$item->{$name});
					$result .= JHtml::_('calendar', $item->{$name} , $name, $fid, '%Y-%m-%d %H:%M:%S', array('class'=>'form-control input-medium '.$required, 'size'=>'25',  'maxlength'=>'19'));
					break;
				case 'date' :
					$item->{$name} = preg_replace("/[^0-9\-]/","",$item->{$name});
					$result .= JHtml::_('calendar', $item->{$name} , $name, $fid, '%Y-%m-%d', array('class'=>'form-control input-small '.$required, 'size'=>'25',  'maxlength'=>'10'));
					break;
				case 'year' :
					$result .= '<input class="form-control input-small '.$required.'" type="text" name="'.$name.'" id="'.$fid.'" value="'.((int) $item->{$name}).'" maxlength="4" size="4" />';
					break;
				case 'time' :
					$result .= '<input class="form-control input-small '.$required.'" type="text" name="'.$name.'"  id="'.$fid.'" value="'.($item->{$name}).'" maxlength="8" size="4" />';
					break;
				case 'text' :
				case 'mediumtext' :
				case 'longtext' :
					$result .= '<div class="form-group d-block mb-3">';
					if ($param=="textarea") {
						$result .= '<textarea class="form-control '.$required.'" name="'.$name.'" id="'.$fid.'">'.$item->{$name}.'</textarea>';
					} else {
						// 	Load the JEditor object
						if (empty(self::$editor)) {
							self::$editor = new JEditor($app->get('editor'));
						}
						$result .= self::$editor->display($name, stripslashes($item->{$name}), '450', '250', '40', '6',false,"joodb_".$fid, array('class'=>'form-control'));
					}
					$result .= '</div>';
					break;
				// special handling for enum and set
				case 'enum' :
					if (count($typevals)<=4 || $param=="radio") {
						foreach ($typevals as $n => $value) {
							$result .= '<div class="form-check form-check-inline">';
							$result .= '<label class="form-check-label" for="'.$fid.$n.'"><input class="form-check-input" type="radio" name="'.$name.'" id="'.$fid.$n.'" value="'.$value.'" '.(( $value == $item->{$name} ) ? 'checked' : '' ).' />&nbsp;'.$value.'</label> ';
							$result .= '</div>';
						}
					} else {
						$result .= '<select class="form-select input-large '.$required.'" type="text" name="'.$name.'" id="'.$fid.'" />';
						$result .= '<option value="" >...</option>';
						foreach ( $typevals as $value )
						{
							$result .= '<option value="' . $value . '" ' . ( ( $value == $item->{$name} ) ? 'selected' : '' ) . '>' . addslashes( $value ) . '</option>';
						}
						$result .= '</select>';
					}
					break;
				case 'set' :
					$setarray = (!is_array($item->{$name})) ? preg_split("/,/",$item->{$name}) : $item->{$name};
					if (count($typevals)<=3 || $param=="check") {
						foreach ($typevals as $n => $value) {
							$result .= '<div class="form-check form-check-inline">';
							$value = str_replace("''","'",$value);
							$result .= '<label class="form-check-label" for="'.$fid.$n.'"><input  class="form-check-input" type="checkbox"  name="'.$name.'[]" id="'.$fid.$n.'" value="'.$value.'" '.((in_array($value,$setarray))? 'checked' : '' ).' />&nbsp;'.$value.'</label> ';
							$result .= '</div>';
						}
					} else {
						$result .= '<select class="form-control input-xxlarge js-choice" '.$required.' type="text" style="width: 100%;" multiple="multiple" name="'.$name.'[]" id="'.$fid.'" >';
						foreach ($typevals as $value) {
							$value = str_replace("''","'",$value);
							$result .= '<option value="'.$value.'" '.(in_array($value,$setarray)? 'selected' : '' ).'>'.$value.'</option>';
						}
						$result .= '</select>';
					}
					break;
				case 'tinyblob' :
				case 'mediumblob' :
				case 'blob' :
				case 'longblob' :
					if (!empty($item->{$name})) $required = "";
					$result .=  '<input class="form-control '.$required.'" type="file" name="'.$name.'" id="'.$fid.'" size="30" />';
					$result .=  '&nbsp;<label  class="form-check-input" ><input  class="form-check-input" type="checkbox" name="'.$name.'_del" value=1 />&nbsp;'.JText::_('EMPTY_FIELD').'</label>';
					if (!empty($item->{$name})) {
						$mime = JoodbAdminHelper::getMimeType($item->{$name});
						$fileurl = JUri::root().'index.php?option=com_joodb&task=getFileFromBlob&joobase='.$joobase->id.'&id='.$item->{$joobase->fid}.'&field='.$name;
						$result .= '<div style="clear:both; font-size: 12px; margin: 5px;" > '.strlen($item->{$name}).' Bytes ('.$mime.')';
						if (substr($mime, 0,5)=="image") {
							$result .= '<a href="'.$fileurl.'" data-featherlight="image" >';
							$result .= '<img style="max-width:80px; max-height: 60px; border: 1px solid #ccc; float:left; margin-right: 15px;" src="data:'.$mime.';base64,'.base64_encode($item->{$name}).'" alt="*" />';
							$result .= '</a>';
						} else {
							$result .= ' &raquo;<a href="'.$fileurl.'" target="_blank">'.JText::_('DOWNLOAD').'</a>&laquo;';
						}
						$result .= '</div>';
					}
					break;
				default:
					$result .= '<input class="form-control input-xlarge '.$required.'" type="text" name="'.$name.'" id="'.$fid.'" value="'.htmlspecialchars($item->{$name}, ENT_COMPAT, 'UTF-8').'" maxlength="'.$typevals[0].'" size="60" style="width: 500px;" />';
			}
		return $result;
	}


	/**
	 * Add subitem select for n:m relations

	 * @param $joobase
	 * @param $subitem
	 * @param $id
	 * @return string
	 */
	public static function getSubitemSelectMulti(&$joobase,&$subitem,$id)
	{
		$result= "";
		$db = $joobase->getTableDBO();
		$query = $db->getQuery(true);
		$query->select("a.`".$subitem->id_field."` AS id, a.`".$subitem->name_field."` AS title");
		$query->from("`".$subitem->table."` AS a");
		$query->order("a.`".$subitem->name_field."` ASC");
		$db->setQuery($query);
		if ($rows = $db->loadObjectList()) {
			$query = $db->getQuery(true);
			$query->select("`".$subitem->idx_id2."`, `".$subitem->idx_id1."`");
			$query->from("`".$subitem->idx_table."`");
			$db->setQuery($query);
			$selected = $db->loadAssocList($subitem->idx_id2);
			$result .= '<div class="controls"><select class="form-select input-xlarge w-100" name="jbSubForm['.$subitem->label.'][]" multiple="multiple" style="height: 180px;">';
			foreach($rows AS $row) {
				$result .= '<option value="'.$row->id.'" ';
				if (isset($selected[$row->id])) $result .= 'selected';
				$result .= '>'.$row->title.'</option>';
			}
			$result .= '</select></div>';
		}
		return $result;
	}

	/**
	 * Add subitem select for n:1 relations

	 * @param $joobase
	 * @param $subitem
	 * @param $id
	 * @return string
	 */
	public static function getSubitemSelect(&$joobase,&$subitem,$id,$required="")
	{
		$result= "";
		$db = $joobase->getTableDBO();
		$query = $db->getQuery(true);
		$query->select("a.`".$subitem->id_field."` AS id, a.`".$subitem->name_field."` AS title");
		$query->from("`".$subitem->table."` AS a");
		$query->order("a.`".$subitem->name_field."` ASC");
		$db->setQuery($query);
		if ($rows = $db->loadObjectList()) {
			$result .= '<div class="form-group"><select class="form-select w-100 '.$required.'" name="'.$subitem->idx_sub.'" '.$required.'>';
			$result .= '<option value="">...</option>';
			foreach($rows AS $row) {
				$result .= '<option value="'.$row->id.'" ';
				if ($row->id==$id) $result .= 'selected';
				$result .= '>'.$row->title.'</option>';
			}
			$result .= '</select></div>';
		}
		return $result;
	}

	/**
	 * Add new DS to joodb table
	 * @param      $jb
	 * @param      $item
	 * @param bool $copy
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since version
	 */
	static function saveData(&$jb,&$item,$copy=false) {
		$app = JFactory::getApplication();
		/* TODO: We should put all input into a array */
		$data = array();
		$app->setUserState('com_joodb.form.data',$data);
		$table = $jb->table;
		$fid = $jb->fid;
		$db	= $jb->getTableDBO();
		// load the jooDb object with table field infos
		$fields = $db->getTableColumns($table,false);
		$errors = 0;
		foreach ($fields as $fname=>$fcell) {
			$fne = str_replace(" ","_",$fname);
			if (isset($_POST[$fne]) || isset($_FILES[$fne])) {
				if (isset($_POST[$fne])) {
					$data[$fname] = $_POST[$fne];
				}
				$typearr = preg_split("/\(/",$fcell->Type);
				switch ($typearr[0]) {
					case 'text' :
					case 'tinytext' :
					case 'mediumtext' :
					case 'longtext' :
						$item->{$fname} = $app->input->post->get($fne,null,'RAW');
						if (empty($item->{$fname})) $item->{$fname}= NULL;
						break;
					case 'int' :
					case 'tinyint' :
					case 'smallint' :
					case 'mediumint' :
					case 'bigint' :
					case 'year' :
						$item->{$fname}= $app->input->getInt($fne,0);
						break;
					case 'date' :
					case 'time' :
					case 'datetime' :
					case 'timestamp' :
						$item->{$fname}= preg_replace("/[^0-9\: \-]/","",$app->input->get($fne, '', 'post', 'string'));
						if (empty($item->{$fname})) $item->{$fname}= NULL;
						break;
					case 'float' :
					case 'decimal' :
						$item->{$fname}= preg_replace("/[^0-9\.,\-]/","",$app->input->get($fne, '', 'post', 'string'));
						$item->{$fname}= str_replace(",",".",$item->{$fname});
						if (empty($item->{$fname})) $item->{$fname}= NULL;
						break;
					case 'set' :
						$values = $app->input->get($fne, array(), 'array');
						$item->{$fname}= join(",",$values);
						break;
					case "tinyblob" :
					case "mediumblob" :
					case "blob" :
					case "longblob" :
						$newf = $app->input->files->get($fne, null);
						if(!empty($newf) && $newf['size'] > 0) {
							$fp = fopen($newf['tmp_name'], 'r');
							$item->{$fname} = fread($fp, filesize($newf['tmp_name']));
						} else if ($app->input->getInt($fne."_del",0)==1) {
							$item->{$fname} = NULL;
						}
						break;
					default:
						$item->{$fname}= $app->input->get($fne, null, 'post','string');
						if (empty($item->{$fname})) $item->{$fname}= NULL;
				}
				if ($fcell->Null=="NO" && ($item->{$fname}===null)) {
					$errors++;
					$app->enqueueMessage( JText::_( 'JLIB_FORM_FIELD_INVALID' ).$fname,'error');
				}
			} else {
				$item->{$fname}=$fcell->Default;
			}
		}

		$app->setUserState('com_joodb.form.data',$data);
		if (!empty($errors)) return false;

		// copy item
		if ($copy===true) {
			$item->{$fid} = null;
		}

		// store creation date
		if (empty($item->{$fid}) && !empty($jb->fdate)) {
			$date = new JDate();
			$item->{$jb->fdate} = $date->toSql();
		}

		// create alias
		$falias=$jb->getSubdata('falias');
		if (!empty($falias)) {
			if (empty($item->{$falias}) || empty($item->{$fid})) {
				$item->{$falias} = JFilterOutput::stringURLSafe( $item->{$jb->ftitle});
			} else {
				$item->{$falias} = JFilterOutput::stringURLSafe( $item->{$falias});
			}
			while (self::checkAlias($jb,$item)) {
				$item->{$falias} = StringHelper::increment($item->{$falias}, 'dash');
			}
		}

		// Force user ID value for non admins!
		$fuser=$jb->getSubdata('fuser');
		if (!empty($fuser)) {
			$user = JFactory::getUser();
			if (empty($item->{$fuser})) $item->{$fuser} = $user->id;
			if ($item->{$fuser}!=$user->id && !$user->authorise('core.admin')) {
				$item->{$fuser} = $user->id;
			}
		}

		try {
			// Update or insert object if ID exists
			if (!empty($item->{$fid})) {
				$db->updateObject($table,$item,$fid,true);
			} else {
				$db->insertObject($table,$item,$fid);
			}
		} catch (Throwable $e) {
			$app->enqueueMessage(JText::_('Error') . " : " . $e->getMessage(), 'error');
			return false;
		}

		$app->enqueueMessage( JText::_( 'Item Saved' ));
		$app->setUserState('com_joodb.form.data',array());
		return true;
	}

	/**
	 * Test for double alias names
	 *
	 * @param $jb
	 * @param $item
	 *
	 * @return bool
	 *
	 * @since version
	 */
	static protected function checkAlias(&$jb,&$item) {
		$falias=$jb->getSubdata('falias');
		$db	= $jb->getTableDBO();
		$db->setQuery("SELECT `".$jb->fid."` FROM `".$jb->table."` WHERE `".$db->escape($falias)."`=".$db->quote($item->{$falias}));
		$id=$db->loadResult();
		return (!empty($id) && $id!=$item->{$jb->fid}) ? true : false;
	}

	/**
	 *
	 * Save porsted subform data
	 *
	 * @param $jb
	 * @param $item
	 *
	 *
	 * @since version
	 * @throws Exception
	 */
	static function saveSubData(&$jb,&$item)
	{
		$app = JFactory::getApplication();
		$id = $item->{$jb->fid};

		// store values from subtemplates
		$subdata = $app->input->get("jbSubForm", null, "array");
		$db      = $jb->getTableDBO();
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
						$sdv                      = new stdClass();
						$sdv->{$subitem->idx_id1} = $id;
						$sdv->{$subitem->idx_id2} = $sdval;
						$db->insertObject($subitem->idx_table, $sdv, "id");
					}
				}
			}
		}
	}

}
