<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

/**
 * Get the current user-name 
 * The complete output of the plugin scripts must got to  
 * $output var. We chose this way to keep plugin
 * writing as simple as possible.  
 * 
 * Other usefull variables are 
 * $joobase (The current Joodatabase object)
 * $part (the part array with function and parametes);
 * You can read passed parameters with the $part->parameter array;
 * Example {joodb user|email}
 */

$user = Factory::getApplication()->getIdentity(); // get the juser object
if (!empty($user->name)) { // if the user is logged in
	$value = (count($part->parameter)>=1) ?  $part->parameter[0] : "name";  // get element from parameter 0 (default name)
	if (isset($user->{$value})) $output .= $user->{$value}; // write to output
}	
