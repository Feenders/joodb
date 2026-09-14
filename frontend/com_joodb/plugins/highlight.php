<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

/**
 * Highlight search matches in field output of catalog
 * (Parameter [0]) field to output
 * Example {joodb highlight|description}
 */
$search = Factory::getApplication()->input->getString("search");
$search = strtolower($search);

if (!empty($search) && isset($item->{$part->parameter[0]})) {
	$content = $item->{$part->parameter[0]};
	preg_match_all('/'.htmlspecialchars(stripcslashes($search), ENT_QUOTES, "UTF-8").'/iU',$content, $matches);
	if (!empty($matches[0])) {
		foreach($matches[0] as $match) {
			$content = str_replace($match, '<span class="hl">'.$match.'</span>',$content);
		}
	}
	$output .= $content;
}	
