<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 */

namespace Feenders\Component\Joodb\Administrator\View\Addmenuitem;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Table\Menu;
use Joomla\CMS\Table\Table;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use function defined;


// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
{
	function display($tpl = null) {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		$app = Factory::getApplication();
		$targetmenu	= $app->input->getString( 'targetmenu');

		$cid	=  $app->input->get( 'cid', array(),  'array' );
		ArrayHelper::toInteger( $cid );
		$id = $cid[0];
		$jb = new \Feenders\Component\Joodb\Administrator\Table\JoodbTable($db);
		$jb->load( $id );

		if (!$targetmenu) { // display menu selector
			$query = 'SELECT menutype AS value, title AS text FROM `#__menu_types`';
			$menu[] = HtmlHelper::_('select.option',  '0', '- '. Text::_( 'Select Menu' ) .' -' );
			$db->setQuery( $query );
			$menu = array_merge( $menu, $db->loadObjectList() );
			$menuselect	= HtmlHelper::_('select.genericlist',  $menu, 'targetmenu', 'class="form-select form-select-sm" size="1"' , 'value', 'text', $targetmenu);

			?>
            <form action="index.php" method="post" name="adminForm">
                <fieldset>
                    <h4 class="mb-4"><?php echo Text::_("JDB_CHOOSE_MENU"); ?> <b><?php echo $jb->name; ?></b></h4>

                    <div class="input-group">
                        <?php echo $menuselect; ?>
                        <div class="input-group-append">
                            <input type="submit" value="<?php echo Text::_("JDB_GO"); ?>" class="btn btn-sm btn-success" />
                        </div>
                    </div>
                    <input type="hidden" name="option" value="com_joodb" />
                    <input type="hidden" name="cid" value="<?php echo $id; ?>" />
                    <input type="hidden" name="view" value="Addmenuitem" />
                    <input type="hidden" name="tmpl" value="component" />
                </fieldset>
            </form>
		<?php } else { // add menu item
			// Detect language to be used.
            $app = Factory::getApplication();
			$language   = Multilanguage::isEnabled() ? $app->getLanguage()->getTag() : '*';
			$langSuffix = ($language !== '*') ? ' (' . $language . ')' : '';

            /** @var  Menu  $table */
            $item = Table::getInstance('menu');
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
			$item->alias = ApplicationHelper::stringURLSafe($item->title);
			while ($this->checkAlias($item->alias)) {
				$item->alias = StringHelper::increment($item->alias, 'dash');
			}
			$item->link = "index.php?option=com_joodb&view=catalog";
			$item->published = 1;
			$item->type = "component";
			if (!$item->check()) echo $item->getError();
			if (!$item->store(true)) echo $item->getError();
			// Rebuild the tree path.
			?>
            <fieldset>
                <h4 class="mb-4"><?php echo Text::_("JDB_ENTRY_CREATED"); ?></h4>
                <?php echo Text::_("JDB_NEW_ENTRY_CREATED"); ?>
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
        $db = Factory::getContainer()->get(DatabaseInterface::class);
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