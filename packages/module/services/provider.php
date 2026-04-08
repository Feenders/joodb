<?php
/**
 * Displays a number of JooDatabase entries on a module position
 *
 * @package joodatabase
 * @subpackage module
 * @version 5.0
 * @author computer :: daten :: netze - feenders - Dirk Hoeschen
 * @link http://joodb.dirk-hoeschen.de
 * @copyright (C) 2012 - 2025 feenders.de. all rights reserved
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The feed module service provider.
 *
 * @since  5.1.0
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   5.1.0
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Feenders\\Module\\Joodb'));
        $container->registerServiceProvider(new HelperFactory('\\Feenders\\Module\\Joodb\\Site\\Helper\\'));

        $container->registerServiceProvider(new Module());
    }
};
