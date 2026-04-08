<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Get the current user IP and store it in a hidden field
 * E.g. for forms
 *
 * Example {joodb userip}
 */

$client  = @$_SERVER['HTTP_CLIENT_IP'];
$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];

if(filter_var($client, FILTER_VALIDATE_IP)) {
    $ip = $client;
} elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
    $ip = $forward;
} else  {
    $ip = $_SERVER['REMOTE_ADDR'];
}

$output .= '<input type="hidden" name="userip" value="'.$ip.'" >';

