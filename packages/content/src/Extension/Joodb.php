<?php
/**
*
* Plugin to display a single Database entry in a normal content article
*
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE
* @author		Dirk Hoeschen (hoeschen@feenders.de)
* @version 	    5.0
*
**/
namespace Joomla\Plugin\Content\Joodb\Extension;

use Feenders\Component\Joodb\Site\Helper\JoodbHelper;
use Feenders\Component\Joodb\Administrator\Table\JoodbTable;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Event\Finder\ResultEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class Joodb extends CMSPlugin implements SubscriberInterface
{

	protected $_joobase = null;
	protected $_data = null;
	protected $_db = null;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onContentPrepare'                => 'onContentPrepare',
			'onFinderResult'                  => 'onFinderResult',
		];
	}

	public function onFinderResult(ResultEvent $event)
	{
		$item = $event->getItem();

		// If the item does not have a text property there is nothing to do
		if (!isset($item->description)) {
			return;
		}

		$item->description = preg_replace('/\{joodbitem (.*?)\}/iU','',$item->description);

	}
	
	/**
	 * Plugin that replaces {joodbitem XX} with a single view of the dataset with ID #XX
	 *
	 *
	 * @param   ContentPrepareEvent  $event  Event instance
	 *
	 * @return  void
	 */
	public function onContentPrepare(ContentPrepareEvent $event)
	{
		// Get content item
		$item = $event->getItem();// If the item does not have a text property there is nothing to do
		if (!isset($item->text)) {
			return;
		}

		// Don't run if in the API Application
		// Don't run this plugin when the content is being indexed
		if ($this->getApplication()->isClient('api') || $event->getContext() === 'com_finder.indexer') {

			preg_replace('/\{joodbitem (.*?)\}/iU','',$item->text);
			return;
		}

		return $this->replaceJoodbContent($item);
	}

	/**
	 * Parse article content and replace finds ...
	 * 
	 * @param object $item
	 * @param object $params
	 */	
	private function replaceJoodbContent(&$item ) {
		// find tags in content-text
		preg_match_all('/\{joodbitem (.*?)\}/iU',$item->text, $matches);
		if (!empty($matches)) {
			$this->loadLanguage("com_joodb",JPATH_BASE);
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$this->_joobase = new JoodbTable($db);
			if (!$this->getJoobase($this->params->get('joobase',1))) return false;
			$app = Factory::getApplication('site');
			$cp = new Registry();
            $jp = ComponentHelper::getParams('com_joodb');
			$cp->merge($jp);
			$cp->set('link_titles','0');
			$cp->set('link_urls','0');

			JoodbHelper::checkAuthorization($this->_joobase,"accessd");
			
			foreach($matches[1] as $match) {
				// parameter 2 use joodb x
				$p = preg_split("/,/", $match);
				if (count($p)>=2) {
					$id = (int) $p[0];
					$this->getJoobase((int) $p[1]);
				} else {
					$id = (int) $match;
				}

                $parts = JoodbHelper::splitTemplate($this->_joobase->tpl_single);
                // remove backbutton e.g.
                $unwanted = array("backbutton","nextbutton","prevbutton");
                foreach ($parts as &$p)
                    if (in_array($p->function,$unwanted))
                        $p->function="";
				
				if ($this->getData($id)) {
					JoodbHelper::prepareDocument();
                    $output =  JoodbHelper::parseTemplate($this->_joobase, $parts,$this->_data,$cp);
				} else $output = Text::sprintf('JDB_ENTRY_NOT_FOUND',$id);
				$item->text = preg_replace("/\{joodbitem ".$match."\}/i",'<div class="joodb database-article">'.$output.'</div>', $item->text);
			}
		}		
		return true;
	}
	

	/**
	 * Method to get Data from table in Database
	 *
	 * @access private
	 * @return boolean true or false
	 */
	private function getData($id)
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_data))
		{
			$statequery = (!empty($this->_joobase->fstate)) ? " AND `".$this->_joobase->fstate."`=1 " : " ";
			$db = $this->_joobase->getTableDBO();
			/* Query single object. */
			$db->setQuery("SELECT * FROM `".$this->_joobase->table
							."` WHERE `".$this->_joobase->fid."`='".(int)$id."'".$statequery." LIMIT 1;");
			$this->_data = $db->loadObject();
		}
		return (empty($this->_data)) ? false : true;
	}	

	/**
	 * Prepare Joodatabase
	 *
	 * @access private
	 * @return boolean true or false
	 */
	private function getJoobase($id)
	{
		if (empty($this->_joobase->id) || $this->_joobase->id!=$id) {
			$this->_joobase->load($id);
            if (empty($this->_joobase) || empty($this->_joobase->id)) return false;
            $this->_db = $this->_joobase->getTableDBO();
			// get the table field list
            $this->_joobase->fields = $this->_db->getTableColumns($this->_joobase->table);
		}
		return (empty($this->_joobase)) ? false : true;
	}
	
}
