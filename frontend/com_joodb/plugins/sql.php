<?php
// no direct access

defined('_JEXEC') or die('Restricted access');
if (count($part->parameter)>=1) {
	$db = JFactory::getDbo();
    $field = $db->escape($part->parameter[0]);
	if ($result = $db->setQuery("SELECT `".$field."` FROM #__YOURTABLE WHERE XXX = YYY")->loadResult()) {
		$output .= '<input type="text" name="'.$field.'" value="'.htmlspecialchars(stripcslashes($result), ENT_COMPAT, 'UTF-8').'" />';
	}
}

