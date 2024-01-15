<?php
// no direct access

/**
 * Get field as hidden form field in form view
 * Example {joodb hiddenfield|category}
 */

defined('_JEXEC') or die('Restricted access');
if (count($part->parameter)>=1) {
    $field = $part->parameter[0];
    $output .= '<input type="hidden" name="'.$field.'" value="'.htmlspecialchars(stripcslashes($this->item->{$field}), ENT_COMPAT, 'UTF-8').'" />';
    $output .= $this->item->{$field};
}

