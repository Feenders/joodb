<?php
/**
 * @version    CVS: 5.0.0
 * @package        JooDatabase - http://joodb.feenders.de
 * @copyright    Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license        GNU/GPL, see LICENSE.php
 * @author        Dirk Hoeschen (hoeschen@feenders.de)
 */

namespace Feenders\Component\Joodb\Administrator\Extension;

defined('_JEXEC') or die();

use Feenders\Component\Joodb\Administrator\Service\Html\DB;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Schemaorg\SchemaorgServiceInterface;
use Joomla\CMS\Schemaorg\SchemaorgServiceTrait;
use Psr\Container\ContainerInterface;
use function define;
use function defined;

/**
 * Component class for Joodb
 *
 * @since  1.0.0
 */
class JoodbComponent extends MVCComponent implements BootableExtensionInterface, RouterServiceInterface
{
	use AssociationServiceTrait;
	use RouterServiceTrait;
	use HTMLRegistryAwareTrait;

	/** @inheritdoc */
	public function boot(ContainerInterface $container) {
		if (!defined('JPATH_COMPONENT_ADMINISTRATOR')) {
			define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_joodb');
			define('JPATH_COMPONENT', JPATH_SITE . '/components/com_joodb');
		}

        $db = $container->get('DatabaseDriver');
		$this->getRegistry()->register('db', new DB($db));
	}


}
