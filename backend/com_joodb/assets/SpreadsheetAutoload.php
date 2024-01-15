<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */


/**
 * PhpSpreadsheet SPL autoloader.
 * @param string $classname The name of the class to load
 */
spl_autoload_register(function ($class_name) {
	$preg_match = preg_match('/^PhpOffice\\\PhpSpreadsheet\\\/', $class_name);

	if (1 === $preg_match) {
		$class_name = preg_replace('/\\\/', '/', $class_name);
		$class_name = preg_replace('/^PhpOffice\\/PhpSpreadsheet\\//', '', $class_name);
		require_once(JPATH_ADMINISTRATOR . '/components/com_joodb/assets/PhpSpreadsheet/' . $class_name . '.php');
	}

	$preg_match = preg_match('/^ZipStream\\\/', $class_name);

	if (1 === $preg_match) {
		$class_name = preg_replace('/\\\/', '/', $class_name);
		require_once(JPATH_ADMINISTRATOR . '/components/com_joodb/assets/' . $class_name . '.php');
	}
	
	$preg_match = preg_match('/^MyCLabs\\\Enum\\\/', $class_name);

	if (1 === $preg_match) {
		$class_name = preg_replace('/\\\/', '/', $class_name);
		$class_name = preg_replace('/^MyCLabs\\/Enum\\//', '', $class_name);
		require_once(JPATH_ADMINISTRATOR . '/components/com_joodb/assets/Enum/' . $class_name . '.php');
	}	
	
});

spl_autoload_register(function ($class_name) {
	$preg_match = preg_match( '/^Psr\\\/', $class_name );

	if ( 1 === $preg_match )
	{
		require_once( JPATH_ADMINISTRATOR . '/components/com_joodb/assets/Psr.php' );
	}

});
