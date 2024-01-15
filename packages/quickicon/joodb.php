<?php
defined('_JEXEC') or die;

/**
 * JooDatabase plugin
 *
 * @package		Joodatabase.Plugin
 * @since		2.5
 */
class plgQuickiconJoodb extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @param       object  $subject The object to observe
	 * @param       array   $config  An array that holds the plugin configuration
	 *
	 * @since       2.5
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}

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
		if ($context != $this->params->get('context', 'mod_quickicon') || !JFactory::getUser()->authorise('core.edit', 'com_joodb')) {
			return;
		}

		$language = JFactory::getLanguage();
		$language->load('com_jce', JPATH_ADMINISTRATOR);

		return array(array(
			'link' => 'index.php?option=com_joodb&view=joodb',
            'image' => 'picture fas fa-database',
			'access'    => array('core.edit"', 'com_joodb'),
			'text' => JText::_('JooDatabase'),
			'id' => 'com_joodb'
		));
	}
}
