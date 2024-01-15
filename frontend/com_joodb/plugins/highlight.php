<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Highlight search matches in field output of catalog
 * (Parameter [0]) field to output
 * Example {joodb highlight|description}
 */

$search = JFactory::getApplication()->input->getString("search");
$content = $item->{$part->parameter[0]};
if (!empty($search) && !empty($content)) {		
	preg_match_all('/'.addslashes($search).'/iU',$content, $matches);
	if (!empty($matches[0])) {
		foreach($matches[0] as $match) {
			$content = preg_replace('/'.$match.'/', '<span class="hl">'.$match.'</span>',$content);
		}
	}
}	
$output .= $content;
