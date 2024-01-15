<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Get the current user-name 
 * The complete output of the plugin scripts must got to  
 * $output var. We choosed this way to keep plugin
 * writing as simple as possible.  
 * 
 * Other usefull variables are 
 * $joobase (The current Joodatabase object)
 * $part (the part array with function and parametes);
 * You can read passed parameters with the $part->parameter array; 
 */

$user = JFactory::getUser(); // get the juser object
if (!empty($user->name)) { // if the user is logged in
	$value = (count($part->parameter)>=1) ?  $part->parameter[0] : "name";  // get element from parameter 0 (default name)
	$output .= $user->{$value}; // write to output
}	
