<?php

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;


/**
 * JooDatabase plugin
 *
 * @package		Joodatabase.Plugin
 * @since		2.5
 */
class plgQuickiconJoodb extends CMSPlugin
{

	/**
	 * Returns an icon definition for an icon
	 *
	 * @param  $context  The calling context
	 *
	 * @return array A list of icon definition associative arrays, consisting of the
	 *				 keys link, image, text and access.
	 * @since       2.5
	 */
	public function onGetIcons($context)
	{
		$app = Factory::getApplication();
		if ($context != $this->params->get('context', 'mod_quickicon') || !$app->getIdentity()->authorise('core.edit', 'com_joodb')) {
			return ;
		}

		$language = $app->getLanguage();
		$language->load('com_joodb', JPATH_ADMINISTRATOR);

		return array(array(
			'link' => 'index.php?option=com_joodb&view=joodb',
            'image' => 'fas fa-database icon-database',
			'access'    => array('core.edit', 'com_joodb'),
			'text' => Text::_('JooDatabase'),
			'id' => 'com_joodb'
		));
	}
}
