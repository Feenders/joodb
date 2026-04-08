<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.joodb
 *
 * @copyright    Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
*/

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Content\Joodb\Extension\Joodb;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.3.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin     = new Joodb(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('content', 'joodb')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
