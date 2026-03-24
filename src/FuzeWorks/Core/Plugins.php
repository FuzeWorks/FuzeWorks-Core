<?php
/**
 * FuzeWorks Framework Core.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2019 TechFuze
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author    TechFuze
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 1.1.4
 *
 * @version Version 1.3.0
 */

namespace FuzeWorks\Core;
use FuzeWorks\Core\ConfigORM\ConfigORM;
use FuzeWorks\Core\Event\PluginGetEvent;
use FuzeWorks\Core\Exception\CoreException;
use FuzeWorks\Core\Exception\FactoryException;
use FuzeWorks\Core\Exception\PluginException;
use ReflectionClass;

/**
 * Plugins Class.
 *
 * Plugins are small component that can be implemented into FuzeWorks and will run upon its start. When FuzeWorks loads, a header file of every plugin will be loaded. This allows plugins to hook into routes, events, factory components and other parts of the framework. This for instance allows the creation of an administration panel, or a contnet management system. 
 * 
 * To make a plugin there are 2 requirements:
 * 1. A plugin class file
 * 2. A plugin header file
 * 
 * To create the plugin, create a directory (with the name of the plugin) in the Plugin folder, inside the Application environment. Next you should add a header.php file in this directory. 
 * This file needs to be in the FuzeWorks\Plugins namespace, and be named *PluginName*Header. For example: TestHeader. 
 * It is required that this header file implements the FuzeWorks\iPluginHeader. All headers must have the init() method. This method will run upon starting FuzeWorks.
 * 
 * Next a plugin class should be created. This file should be named the same as the folder, and be in the Application\Plugin namespace. An alternative classname can be set in the header, by creating a public $className variable. This plugin can be called using the $plugins->get() method.
 *
 * @todo      Add methods to enable and disable plugins
 * @author    TechFuze <contact@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Plugins
{
    use ComponentPathsTrait;

	/**
	 * Array of loaded Plugins, so that they won't be reloaded
	 * Plugins only end up here after being explicitly loaded. Header files do NOT count.
	 * 
	 * @var array Array of loaded plugins
	 */
	protected array $plugins = array();

	/**
	 * Array of plugin header classes. 
	 * Loaded upon startup. Provide details on what class should load for the plugin. 
	 * 
	 * @var array Array of loaded plugin header classes
	 */
	protected array $headers = array();

	/**
	 * Config file for the plugin system 
	 * 
	 * @var ConfigORM
	 */	
	protected ConfigORM $cfg;

    /**
     * Called upon initialization of the Container
     *
     * @throws FactoryException
     * @codeCoverageIgnore
     */
	public function __construct()
	{
		$this->cfg = Factory::getInstance()->config->getConfig('plugins');
	}

    /**
     * Load the header files of all plugins. 
     */
	public function loadHeadersFromPluginPaths()
	{
		// Cycle through all pluginPaths
        for ($i=Priority::getHighestPriority(); $i<=Priority::getLowestPriority(); $i++)
        {
            if (!isset($this->componentPaths[$i]))
                continue;

            foreach ($this->componentPaths[$i] as $pluginPath) {

                // If directory does not exist, skip it
                if (!file_exists($pluginPath) || !is_dir($pluginPath))
                    continue;

                // Fetch the contents of the path
                $pluginPathContents = array_diff(scandir($pluginPath), array('..', '.'));

                // Now go through each entry in the plugin folder
                foreach ($pluginPathContents as $pluginFolder) {
                    // @codeCoverageIgnoreStart
                    if (!is_dir($pluginPath . DS . $pluginFolder))
                        continue;
                    // @codeCoverageIgnoreEnd

                    // If a header file exists, use it
                    $file = $pluginPath . DS . $pluginFolder . DS . 'header.php';
                    $pluginFolder = ucfirst($pluginFolder);
                    $className = '\Application\Plugin\\'.$pluginFolder.'Header';
                    if (file_exists($file))
                    {
                        // Load the header file
                        require_once($file);
                        $header = new $className();
                        if (!$header instanceof iPluginHeader)
                            continue;

                        // Load the header
                        $this->loadHeader($header);
                    }
                }
            }
        }
	}

    /**
     * Load a header object.
     *
     * The provided header will be loaded into the header registry and initialized.
     *
     * @param iPluginHeader $header
     * @return bool
     */
	protected function loadHeader(iPluginHeader $header): bool
    {
        // Fetch the name
        $pluginName = ucfirst($header->getName());

        // Check if the plugin is disabled
        if (in_array($pluginName, $this->cfg->get('disabled_plugins')))
        {
            $this->headers[$pluginName] = 'disabled';
            return false;
        }

        // Initialize it
        $h = $this->headers[$pluginName] = $header;
        $h->init();

        // And log it
        Logger::log('Loaded Plugin Header: \'' . $pluginName . '\'');
        return true;
    }

    /**
     * Add a Plugin to FuzeWorks
     *
     * The provided plugin header will be loaded into the registry and initialized
     *
     * @param iPluginHeader $header
     * @return bool
     */
    public function addPlugin(iPluginHeader $header): bool
    {
        return $this->loadHeader($header);
    }

    /**
     * Get a plugin.
     *
     * @param string $pluginName Name of the plugin
     * @param array|null $parameters Parameters to send to the __construct() method
     * @return mixed Plugin on success, bool on cancellation
     * @throws \FuzeWorks\Core\Exception\EventException
     * @throws PluginException
     */
	public function get(string $pluginName, ?array $parameters = null)
	{
		if (empty($pluginName))
			throw new PluginException("Could not load plugin. No name provided", 1);

		// Determine the name of the plugin
		$pluginName = ucfirst($pluginName);

		// Fire pluginGetEvent, and cancel or return custom plugin if required
        /** @var PluginGetEvent $event */
        $event = Events::fireEvent('PluginGetEvent', $pluginName);
		if ($event->isCancelled())
			return false;
		elseif ($event->getPlugin() != null)
			return $event->getPlugin();

		// Otherwise, just set the variables
		$pluginName = $event->pluginName;

		// Check if the plugin is already loaded and return directly
		if (isset($this->plugins[$pluginName]))
			return $this->plugins[$pluginName];

		// Check if the plugin header exists
		if (!isset($this->headers[$pluginName]))
			throw new PluginException("Could not load plugin. Plugin header does not exist", 1);

		// If disabled, don't bother
		if (in_array($pluginName, $this->cfg->get('disabled_plugins')))
			throw new PluginException("Could not load plugin. Plugin is disabled", 1);

		// Determine what file to load
        /** @var iPluginHeader $header */
		$header = $this->headers[$pluginName];

		// Add to autoloader
        $headerReflection = new ReflectionClass( get_class($header) );
        $prefix = $header->getClassesPrefix();
        $filePath = dirname($headerReflection->getFileName()) . (!empty($header->getSourceDirectory()) ? DS . $header->getSourceDirectory() : '');
        $pluginClass = $header->getPluginClass();
        if (!is_null($prefix) && !is_null($filePath))
        {
            try {
                Core::addAutoloadMap($prefix, $filePath);
            } catch (CoreException $e) {
                throw new PluginException("Could not load plugin. Autoloader invalid: '".$e->getMessage()."'");
            }
        }

		// If a 'getPlugin' method is found in the header, call that instead
		if (method_exists($header, 'getPlugin'))
		{
			$this->plugins[$pluginName] = $header->getPlugin();
			Logger::log('Loaded Plugin: \'' . $pluginName . '\'');
			return $this->plugins[$pluginName];
		}

		// Attempt to load the plugin
		if (!class_exists($pluginClass, true))
			throw new PluginException("Could not load plugin. Class does not exist", 1);

		$this->plugins[$pluginName] = new $pluginClass($parameters);
		Logger::log('Loaded Plugin: \'' . $pluginName . '\'');

		// And return it
		return $this->plugins[$pluginName];
	}
}