<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Installer\InstallerScript;

defined( '_JEXEC' ) or die( 'Restricted access' );

/**
 * Script JooDB installer script
 */
class com_joodbInstallerScript extends InstallerScript {

	var $joodb_version = "5.0";
	var $msgs = array();

	/**
	 * method to install the component
	 * @return void
	 */
	function install($parent) {

		$installer      = Installer::getInstance();
		$requirements   = self::checkRequirements();

		if ($requirements !== true) {
			$installer->set('message', $requirements);
			$installer->abort();
			return false;
		}

		@ini_set('max_execution_time',0);
		@ini_set('memory_limit','256M');

		$db = Factory::getContainer()->get('DatabaseDriver');

        // Create image directory
		$imagedb = JPATH_SITE.'/images/joodb/';
		if (!file_exists($imagedb)){
			$this->msgs[] = (mkdir($imagedb, 0775)) ? "Imagedirectory ".$imagedb." was created" : "Unable to create image directory. Please make sure that /images ist writable!";
		}

		$version = self::get_installed_version();
		if ($version) { // update from old version
			$this->msgs[] = "JooDatabase was updated from version ".$version;
		} else{
			$this->msgs[] = ($this->insert_sql_file("install-complete.sql")) ? "JooDatabase SQL-tables were installed": "<b>Error</b> installing JooDatabase SQL-tables";
			// install complete
			$db->setQuery( "UPDATE `#__joodb` SET `table` = '".$db->getPrefix()."joodb_sample' WHERE `#__joodb`.`id` =1 LIMIT 1 ;" );
			$db->execute();
		}

		$db->setQuery("INSERT INTO `#__joodb_settings` (`name`, `value`) VALUES ('version', '".$this->joodb_version."')");
		$db->execute();

		// install joodb.css only if it does not exist
		$cpath = JPATH_SITE.'/components/com_joodb';
		if (file_exists($cpath.'/assets.org')) {
			$this->rrmdir($cpath."/assets.org");
		}

		$packages = $installer->getPath('source') . '/packages';

		// install additional packages
		if (is_dir($packages)) {
			$message = "Installation of Plugins and Modules<ul>";
			$message .= self::installPackages($packages);
			$message .= "</ul>";
			$this->msgs[] = $message;
		}

		?>
        <hr />
        <h2>Thank you for using JooDatabase</h2>
        <h4>JooDatabase was made by</h4>
        <h3>Computer &sdot; Daten &sdot; Netze &bull; Feenders</h3>
        <ul>
            <li>Author: Dirk Hoeschen (<a href="mailto:service@feenders.de">service@feenders.de</a>)</li>
        </ul>
        <blockquote><i><b>ATTENTION!</b>Version 3.6 changes the CCS files and includes its own icon font.<br/>
                If you want to keep your old styles you can move joodb.css from the assets folder to
                your template CSS folder.</i></blockquote>
        <p>Feenders does not offer personal support for this version. However:
            If you need professional support or want individual modifications, ask
            for conditions.</p>
        <p>For more informations (user forum,help,FAQs and examples), look at <a
                    href="https://joodb.feenders.de" target="_blank"
                    title="joodb.feenders.de/support">joodb.feenders.de/support</a>.
        </p>
        <p>
            Visit the <a href="http://joodb.feenders.de">JooDatabase site</a> for
            the lastest news and updates.
        </p>
        <p>
            If you like our program please vote for us at <a
                    href="https://extensions.joomla.org/extensions/core-enhancements/coding-a-scripts-integration/custom-code-in-content/20892"
                    target="_blank">extensions.joomla.org</a>!
        </p>
        <br />
        <b>Installation messages</b>
        <ul>
			<?php foreach ($this->msgs as $msg) echo "<li>".$msg."</li>"; ?>
        </ul>
		<?php

	}


	/**
	 * method to uninstall the component
	 * @return void
	 */
	function uninstall($parent) {
		global $errors;

		/*		$db = $db = Factory::getContainer()->get(DatabaseInterface::class);
				$db->setQuery( "DROP TABLE IF EXISTS `#__joodb`" )->query();
				$db->setQuery( "DROP TABLE IF EXISTS `#__joodb_sample`" )->query();
				$db->setQuery( "DROP TABLE IF EXISTS `#__joodb_settings`" )->query();*/

		echo '<div style="text-align: center;">'
			.'<h2>Thank you for using JooDatabase</h2>'
			.'<p>The component JooDatabase was succesfully uninstalled!</p>'
			.'<p style="color: #d40000; font-weight: bold;">Remember: Your JooDB'
			.'database tables jos_joodb and jos_joodb_settings need to be removed manually.</p>'
			.'</div><br />';
	}

	/**
	 * method to update the component
	 *
	 * @return void
	 */
	function update($parent) {
		// $parent is the class calling this method
		$this->install($parent);
	}

	/**
	 * method to run before an install/update/uninstall method
	 * @return void
	 */
	function preflight($type, $parent)	 { }

	/**
	 * method to run after an install/update/uninstall method
	 * @return void
	 */
	function postflight($type, $parent) { }

	/**
	 * Install additional packages search and quickicon plugins / As done in JCE
	 * @return string or false
	 * @param object $path[optional] Path to package folder
	 */
	private static function installPackages($source) {
		$result = '';

		// install quickicon
        $db = Factory::getContainer()->get('DatabaseDriver');
        $installer = new Installer();
        $installer->setDatabase($db);
		$installer->setOverwrite(true);
		if ($installer->install($source . '/quickicon')) {
			$result .= '<li class="success">Quickicon Plugin installed!</li>';
            $plugin = Table::getInstance('extension');
			$id = $plugin->find(array('type' => 'plugin', 'folder' => 'quickicon', 'element' => 'joodb'));
			$plugin->load($id);
            $plugin->publish($id);
		} else {
			$result .= '<li class="error">' . Text::_($installer->message, $installer->message) . '</li>';
		}

		// install search plugin
        $installer = new Installer();
        $installer->setDatabase($db);
		$installer->setOverwrite(true);
		if ($installer->install($source . '/finder')) {
			$result .= '<li class="success">Smart-Search Plugin installed and activated! Dont forget to reindex.</li>';
            $plugin = Table::getInstance('extension');
			$id = $plugin->find(array('type' => 'plugin', 'folder' => 'finder', 'element' => 'joodb'));
			$plugin->load($id);
			$plugin->publish($id);
		} else {
			$result .= '<li class="error">' . Text::_($installer->message, $installer->message) . '</li>';
		}

		// install content plugin
        $installer = new Installer();
        $installer->setDatabase($db);
		$installer->setOverwrite(true);
		if ($installer->install($source . '/content')) {
			$result .= '<li class="success">Content Plugin installed but yet unpublished!</li>';
/*            $plugin = Table::getInstance('extension');
			$id = $plugin->find(array('type' => 'plugin', 'folder' => 'content', 'element' => 'joodb'));
			$plugin->load($id);*/
			// $plugin->publish();
		} else {
			$result .= '<li class="error">' . Text::_($installer->message, $installer->message) . '</li>';
		}

		// install JooDB Module
        $installer = new Installer();
        $installer->setDatabase($db);
		$installer->setOverwrite(true);
		if ($installer->install($source . '/module')) {
			$result .= '<li class="success">JooDB module installed!</li>';
		} else {
			$result .= '<li class="error">' . Text::_($installer->message, $installer->message) . '</li>';
		}

		return $result;
	}

	/**
	 * Check joomla and php version
	 * @return bool|string
	 */
	private static function checkRequirements() {
		$requirements = array();

		if ((int)JVERSION[0]<"4") {
			$requirements[] = array(
				'name' => 'Joomla Version',
				'info' => 'This version of JooDB requires 4.x or later. Please get old version from joodb.feenders.de'
			);
		}

		// check PHP version
		if (version_compare(PHP_VERSION, '8.1.0', '<')) {
			$requirements[] = array(
				'name' => 'PHP Version',
				'info' => '; Requires PHP version 8.1 or later. Your version is : ' . PHP_VERSION
			);
		}

		if (!empty($requirements)) {
			$message = '<h2>Install Failed</h2>';
			$message .= '<h3>This installation of Joomla does not fullfill all technical requirements (see below)</h3>';
			$message .= '<ul>';
			foreach ($requirements as $requirement) {
				$message .= '<li class="error">' . $requirement['name'] . ' : ' . $requirement['info'] . '<li>';
			}
			$message .= '</ul>';
			$message .= '</div>';

			return $message;
		}

		return true;
	}


	/**
	 * Check if joodb is already installed ...
	 * Returns versionnumber. false if not exist.
	 */
	private static function get_installed_version(){
        $db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery("SHOW TABLES LIKE '".$db->getPrefix()."joodb'");
		if ($test=$db->loadResult()){
			$db->setQuery("SELECT value FROM `#__joodb_settings` WHERE  `name` = 'version' LIMIT 0 , 1");
			return ($version=$db->loadResult()) ? $version : "1.0";
		} else{
			return false;
		}
	}

	/**
	 * Insert a sql file into database
	 * @param string $filename
	 * @return bool
	 */
	private function insert_sql_file($filename){
		$path = JPATH_ADMINISTRATOR.'/components/com_joodb/assets/';
		if (file_exists($path.$filename)){
            $db = Factory::getContainer()->get('DatabaseDriver');
			$sql = file_get_contents($path.$filename);
			$queries = DatabaseDriver::splitSql($sql);
			foreach ($queries as $query){
					try {
						$db->setQuery($query)->execute();
					} catch (Exception $e) {
						echo Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()) . '<br>';
						return false;
					}
			}
			return true;
		}
        return false;
	}

	/**
     * Remove Directory
     *
	 * @param string $dir
	 */
	private function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir."/".$object))
						$this->rrmdir($dir."/".$object);
					else
						unlink($dir."/".$object);
				}
			}
			rmdir($dir);
		}
	}


}
