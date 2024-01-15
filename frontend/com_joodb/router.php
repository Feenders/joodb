<?php

\defined('JPATH_PLATFORM') or die;

/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) 2021 Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 * @since   4.0
 */


use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Extension\Component;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;


/*
 * New Router fpr Joomla 4
 */
class JoodbRouter extends RouterView {

	public function __construct( $app = null, $menu = null )
	{

		$crv = new RouterViewConfiguration('catalog');
		$crv->setKey('alias');
		$crv->setKey('joobase');
		$crv->setKey('letter');
		$crv->setKey('view');
		$crv->setKey('id');
		$this->registerView($crv);

		$arv = new RouterViewConfiguration('article');
		$arv->setKey('id')->setParent($crv);
		$this->registerView($arv);

		parent::__construct( $app, $menu );

		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));

	}

	/**
	 * Build URi
	 *
	 * @param   array  $query
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since 4.0
	 */
	public function build( &$query ) {

		$segments = array();

		// get a menu item based on Itemid or currently active
		$app = JFactory::getApplication();
		$menu = $app->getMenu();
		// we need a menu item.  Either the one specified in the query, or the current active one if none specified
		if (empty($query['Itemid'])) {
			$db=JFactory::getDbo();
			if (isset($query['joobase'])) {
				$query['Itemid'] = $db->setQuery("SELECT `id` FROM `#__menu` WHERE `published` = 1 AND `link`='index.php?option=com_joodb&view=catalog' AND `params` LIKE '{\"joobase\":\"".(int)$query['joobase']."\"%'",0,1)->loadResult();
			}
			if (!empty($query['Itemid'])) {
				$menuItem = $menu->getItem($query['Itemid']);
			} else {
				$menuItem = $menu->getActive();
			}
		} else {
			$menuItem = $menu->getItem($query['Itemid']);
			$mv = isset($menuItem->query['view']) ?  $menuItem->query['view'] : null;
			if ( $mv == 'catalog' ) {
				unset( $query['Itemid'] );
			}
		}

		$mv = isset($menuItem->query['view']) ?  $menuItem->query['view'] : null;

		if(isset($query['view']))
		{
			if ($mv==$query['view']) {
				unset($query['view']);
			} else {
				if ($query['view']!="article" || (!empty($menuItem) &&  $menuItem->query['option']!="com_joodb")) {
					$segments[] = $query['view'];
				}
				unset($query['view']);
			}
		}

		if(isset($query['joobase'])) {
			$params = $menuItem->getParams();
			if ($params->get('joobase')!=$query['joobase']) {
				$segments[] = $query['joobase'];
			}
			unset($query['joobase']);
		}

		if(isset($query['id'])) {
			$segments[] = str_replace(":","-",$query['id']);
			unset($query['id']);
		}

		if(isset($query['alias'])) {
			$segments[] = $query['alias'];
			unset($query['alias']);
		}

		return $segments;
	}

	/**
	 * Parse URI
	 * @param   array  $segments
	 * @return array
	 *
	 * @since 4.0
	 */
	public function parse(&$segments)
	{
		$vars = array();
		// Count route segments
		$count = count($segments) - 1;

		//routing for articles if menu item unknown joodb ID is included
		if ($count >= 2)
		{
			$vars['view']    = $segments[$count - 2];
			$id              = explode('-', $segments[$count - 1]);
			$vars['joobase'] = (int) $id[0];
			$id              = explode('-', $segments[$count]);
			$vars['id']      = (int) $id[0];
		}
		else if ($count == 1)
		{
			$id = explode(':', $segments[0]);
			if (is_numeric($id[0]))
			{
				$vars['joobase'] = (int) $id[0];
				$vars['view']    = 'article';
			}
			else
			{
				$vars['view'] = $segments[0];
			}
			$id  = explode(':', $segments[1]);
			$vars['id'] = (int) $id[0];
		}
		else
		{
			$id = explode('-', $segments[0]);
			$vars['view'] = "article";
			if (is_numeric($id[0]))
			{
				$vars['id'] = $id[0];
			}
			else
			{
				$vars['alias'] = str_replace(":", "-", $segments[0]);
			}
		}

		$segments = array();
		return $vars;
	}
}
