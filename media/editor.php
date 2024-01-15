<?php
/**
* @package		JooDatabase - http://joodb.feenders.de
* @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @author		Dirk Hoeschen (hoeschen@feenders.de)
*/

defined('_JEXEC') or die('Restricted access');

JHtml::_('script', 'media/com_joodb/js/cm/codemirror.js', array('version' => 'auto', 'relative' => false));
JHtml::_('script', 'media/com_joodb/js/cm/addon/mode/overlay.js', array('version' => 'auto', 'relative' => false));
JHtml::_('script', 'media/com_joodb/js/cm/mode/xml/xml.js', array('version' => 'auto', 'relative' => false));
JHtml::_('script', 'com_joodb/editor.js', array('version' => 'auto', 'relative' => true));
JHtml::_('stylesheet', 'com_joodb/codemirror.css', array('version' => 'auto', 'relative' => true));

/**
 * Load special CodeMirror Editor and handle as JEditor Object
 */
class JDBEditor 
{
	/**
	 * Editor Plugin object
	 *
	 * @var  object
	 */
	protected $_editor = null;

	/**
	 * Editor Plugin name
	 *
	 * @var  string
	 */
	protected $_name = null;


	/**
	 * Constructor
	 *
	 * @param   string  The editor name
	 */
	public function __construct($editor = 'none')
	{
		$this->_name = $editor;
	}

	/**
	 * Display the editor area.
	 *
	 * @param   string   $name      The control name.
	 * @param   string   $html      The contents of the text area.
	 * @param   string   $width     The width of the text area (px or %).
	 * @param   string   $height    The height of the text area (px or %).
	 * @param   integer  $col       The number of columns for the textarea.
	 * @param   integer  $row       The number of rows for the textarea.
	 * @param   boolean  $buttons   True and the editor buttons will be displayed.
	 * @param   string   $id        An optional ID for the textarea (note: since 1.6). If not supplied the name is used.
	 * @param   string   $asset     The object asset
	 * @param   object   $author
	 * @param   array    $params    Associative array of editor parameters.
	 *
	 * @return  string
	 *
	 * @since   11.1
	 */
	public function display($name, $html, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array())
	{
		$return = '<textarea id="'.$name.'" name="'.$name.'" class="cmeditor">';
		$return .= $html;
		$return .='</textarea>';
		return $return;
	}

}
