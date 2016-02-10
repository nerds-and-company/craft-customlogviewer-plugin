<?php

namespace Craft;

/**
 * Composer Class Loader Plugin
 */
class CustomlogviewerPlugin extends BasePlugin
{
    const VERSION = 'v0.1.0';

    /**
     * Returns the plugin’s version number.
     *
     * @return string The plugin’s version number.
     */
    final public function getVersion()
    {
        return self::VERSION;
    }

    /**
     * @return string
     */
    final public function getName()
    {
        return Craft::t('Custom Log Viewer');
    }

    /**
     * Returns the plugin developer’s name.
     *
     * @return string The plugin developer’s name.
     */
    final public function getDeveloper()
    {
        return 'Nerds and Company';
    }

    /**
     * Returns the plugin developer’s URL.
     *
     * @return string The plugin developer’s URL.
     */
    final public function getDeveloperUrl()
    {
        return 'https://github.com/nerds-and-company/craft-monolog-plugin/';
    }

    final public function onAfterInstall()
    {
        // @TODO: Do we want to validate that Monolog is actually available? What happens if it is not?
    }

    /**
     * Whether this plugin has its own section tab in the CP.
     *
     * @return bool
     */
    final public function hasCpSection()
    {
        return true;
    }

    /**
     * Register routes for Control Panel.
     *
     * @return array
     */
    final public function registerCpRoutes()
    {
        return array(
            /*  */
            'customlogviewer' => ['action' => 'customlogviewer/customlogviewer/index'],
            'customlogviewer/(?P<currentLogFileName>.+)' => ['action' => 'customlogviewer/customlogviewer/index'],
            'utils/customlogs/' => ['action' => 'customlogviewer/customlogviewer/index'],
            'utils/customlogs/(?P<currentLogFileName>.+)' => ['action' => 'customlogviewer/customlogviewer/index'],
        );
    }
}
