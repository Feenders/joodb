<?php
defined('_JEXEC') or die('Restricted access');
if (count($part->parameter)>=1) {
    $field = $part->parameter[0];
    $output .=  number_format(floatval($item->{$field}), 2, ",", " ");
}
