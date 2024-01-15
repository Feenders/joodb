<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Calculate the total of a float or integer field
 * in catalog footer or header context
 *
 * Example {joodb summary|price|2}
 */

$summary = 0;
if (isset($part->parameter[0]) && !empty($this->items)) {
	$field = $part->parameter[0];
	$decimals = (isset($part->parameter[1])) ? (int) $part->parameter[1] : 0;
	foreach ($this->items AS $item) {
		$summary += (float) $item->{$field};
	}
	$output .= number_format($summary, $decimals, JText::_('DECIMALS_SEPARATOR'), JText::_('THOUSANDS_SEPARATOR'));
}