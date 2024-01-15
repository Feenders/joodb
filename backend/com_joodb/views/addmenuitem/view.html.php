<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

// no direct access
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

use Joomla\CMS\Language\Multilanguage;
use Joomla\Utilities\ArrayHelper;
use Joomla\String\StringHelper;

// edit or add a custom entry from a table
class JoodbViewAddMenuItem extends JViewLegacy {

	function display($tpl = null) {
		$db	= JFactory::getDBO();
		$app = JFactory::getApplication();
		$targetmenu	= $app->input->getString( 'targetmenu');

		$cid	=  $app->input->get( 'cid', array(),  'array' );
		ArrayHelper::toInteger( $cid );
		$id = $cid[0];
		$jb = JTable::getInstance( 'joodb', 'Table' );
		$jb->load( $id );

		if (!$targetmenu) { // display menu selector
			$query = 'SELECT menutype AS value, title AS text FROM `#__menu_types`';
			$menu[] = JHTML::_('select.option',  '0', '- '. JText::_( 'Select Menu' ) .' -' );
			$db->setQuery( $query );
			$menu = array_merge( $menu, $db->loadObjectList() );
			$menuselect	= JHTML::_('select.genericlist',  $menu, 'targetmenu', 'class="form-select form-select-sm" size="1"' , 'value', 'text', $targetmenu);

			?>
            <form action="index.php" method="post" name="adminForm">
                <fieldset>
                    <h4 class="mb-4"><?php echo JText::_( 'Choose menu' ); ?> <b><?php echo $jb->name; ?></b></h4>

                    <div class="input-group">
                        <?php echo $menuselect; ?>
                        <div class="input-group-append">
                            <input type="submit" value="<?php echo JText::_( 'Go' ); ?>" class="btn btn-sm btn-success" />
                        </div>
                    </div>
                    <input type="hidden" name="option" value="com_joodb" />
                    <input type="hidden" name="cid" value="<?php echo $id; ?>" />
                    <input type="hidden" name="view" value="AddmenuItem" />
                    <input type="hidden" name="tmpl" value="component" />
                </fieldset>
            </form>
		<?php } else { // add menu item
			$version = new JVersion();
			// Detect language to be used.
			$language   = Multilanguage::isEnabled() ? JFactory::getLanguage()->getTag() : '*';
			$langSuffix = ($language !== '*') ? ' (' . $language . ')' : '';
			$item = JTable::getInstance('menu');
			$item->menutype = $targetmenu;
			$item->id=0;
			$item->title = ucfirst($jb->name);
			$item->access = 1;
			$item->client_id = 0;
			$item->language = "*";
			$item->setLocation(1, 'last-child');
			$db->setQuery( "SELECT extension_id FROM #__extensions WHERE  `element` =  'com_joodb'",0,1 );
			$item->component_id = $db->loadResult();
			$item->params =  '{"joobase":"'.$jb->id.'","where_statement":"","show_description":"0","description":"","image":"-1","image_align":"right","link_titles":"1","link_urls":"0","orderby":"ftitle","ordering":"ASC","limit_to_user":"0","search_all":"1","limit":"5","exportfields":"","eportlimit":"100","menu-anchor_title":"","menu-anchor_css":"","menu_image":"","menu_text":1,"page_title":"","show_page_heading":0,"page_heading":"","pageclass_sfx":"","menu-meta_description":"","menu-meta_keywords":"","robots":"","secure":0}';
			$item->alias = JApplicationHelper::stringURLSafe($item->title);
			while ($this->checkAlias($item->alias)) {
				$item->alias = StringHelper::increment($item->alias, 'dash');
			}
//			$item->path = $item->alias;
			$item->link = "index.php?option=com_joodb&view=catalog";
			$item->published = 1;
			$item->type = "component";
			if (!$item->check()) echo $item->getError();
			if (!$item->store(true)) echo $item->getError();
			// Rebuild the tree path.
//			if (!$item->rebuildPath($item->id)) die($item->getError());
			?>
            <fieldset>
                <h4 class="mb-4"><?php echo JText::_( 'Entry created' ); ?></h4>
                <?php echo JText::_( 'New entry created' ); ?>
            </fieldset>
			<?php
		}
	}

	/**
	 * Schaut ob alias in tabelle vorhanden ist
	 * @param $alias
	 * @param $table
	 * @return bool
	 */
	private function checkAlias($alias) {
        $db = JFactory::getDbo();
		$query = $db->getQuery(true)
		            ->select('id')
		            ->from($db->quoteName('#__menu'))
		            ->where($db->quoteName('parent_id') . ' = 1')
		            ->where($db->quoteName('client_id') . ' = 0')
		            ->where($db->quoteName('alias') . ' = ' . $db->quote($alias));
		$id=$db->setQuery($query)->loadResult();
		return (!empty($id)) ? true : false;
	}

}