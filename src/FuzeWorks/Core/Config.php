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
 * @since Version 0.0.1
 *
 * @version Version 1.3.0
 */

namespace FuzeWorks\Core;
use FuzeWorks\Core\ConfigORM\ConfigORM;
use FuzeWorks\Core\Event\ConfigGetEvent;
use FuzeWorks\Core\Exception\ConfigException;
use FuzeWorks\Core\Exception\EventException;

/**
 * Config Class.
 *
 * This class gives access to the config files. It allows you to open configurations and edit them.
 *
 * @author    TechFuze <contact@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Config
{
    use ComponentPathsTrait;

    public function __construct() {
        $this->addComponentPath(dirname(Core::$coreDir) . DS . 'Config', Priority::LOWEST);
    }

    /**
     * Array where all config files are saved while FuzeWorks runs
     * 
     * @var array Array of all loaded config file ORM's
     */
    protected array $cfg = [];

    /**
     * Array of config values that will be overridden
     *
     * @var array of config values
     */
    public static array $configOverrides = [];

    /**
     * Retrieve a config file object
     * 
     * @param string $configName  Name of the config file. E.g. 'main'
     * @param array  $configPaths Optional array of where to look for the config files
     * @return  ConfigORM of the config file. Allows for easy reading and editing of the file
     * @throws  ConfigException
     */
    public function getConfig(string $configName, array $configPaths = []): ConfigORM
    {
        // Determine the config name
        $configName = strtolower($configName);

        // If it's already loaded, return the existing object
        if (isset($this->cfg[$configName]))
            return $this->cfg[$configName];

        // First determine what directories to use
        $paths = [];
        if (!empty($configPaths))
            $paths[3] = $configPaths;
        else
            $paths = $this->componentPaths;

        // Otherwise, try and load a new one
        $this->cfg[$configName] = $this->loadConfigFile($configName, $paths);
        return $this->cfg[$configName];
    }

    /**
     * @param $configName
     * @return ConfigORM
     * @throws ConfigException
     */
    public function get($configName): ConfigORM
    {
        return $this->getConfig($configName);
    }

    /**
     * @param $configName
     * @return ConfigORM
     * @throws ConfigException
     */
    public function __get($configName): ConfigORM
    {
        return $this->getConfig($configName);
    }

    /**
     * Clears all the config files and discards all changes not committed
     */
    public function discardConfigFiles()
    {
        $this->cfg = [];
    }

    /**
     * Determine whether the file exists and, if so, load the ConfigORM
     * 
     * @param string $configName  Name of the config file. E.g. 'main'
     * @param array  $configPaths Required array of where to look for the config files
     * @return  ConfigORM of the config file. Allows for easy reading and editing of the file
     * @throws  ConfigException
     */
    protected function loadConfigFile(string $configName, array $configPaths): ConfigORM
    {
        // Fire event to intercept the loading of a config file
        try {
            /** @var ConfigGetEvent $event */
            $event = Events::fireEvent('ConfigGetEvent', $configName, $configPaths);
            // @codeCoverageIgnoreStart
        } catch (EventException $e) {
            throw new ConfigException("Could not load config. ConfigGetEvent fired exception: '" . $e->getMessage() . "''", 1);
            // @codeCoverageIgnoreEnd
        }

        // If cancelled, load empty config
        if ($event->isCancelled())
            return new ConfigORM();

        // Cycle through all priorities if they exist
        $configORM = new ConfigORM();
        for ($i=Priority::getHighestPriority(); $i<=Priority::getLowestPriority(); $i++)
        {
            if (!isset($event->configPaths[$i]))
                continue;

            // Cycle through all directories
            foreach ($event->configPaths[$i] as $configPath)
            {
                // If file exists, load it and break the loop
                $file = $configPath . DS . 'config.'.strtolower($event->configName).'.php';
                if (file_exists($file))
                    $configORM->addFile($i, $file);
            }
        }

        // And initialize the ORM
        $configORM->init();

        // Override config values if they exist
        if (isset(self::$configOverrides[$event->configName]))
        {
            foreach (self::$configOverrides[$event->configName] as $configKey => $configValue)
                $configORM->{$configKey} = $configValue;
        }

        if ($configORM->loaded)
            return $configORM;

        throw new ConfigException("Could not load config. File $event->configName not found", 1);
    }

    /**
     * Override a config value before FuzeWorks is loaded.
     *
     * Allows the user to change any value in config files loaded by FuzeWorks.
     *
     * @param string $configName
     * @param string $configKey
     * @param $configValue
     */
    public static function overrideConfig(string $configName, string $configKey, $configValue)
    {
        // Convert configName
        $configName = strtolower($configName);

        // If config doesn't exist yet, create it
        if (!isset(self::$configOverrides[$configName]))
            self::$configOverrides[$configName] = [];

        // And set the value
        self::$configOverrides[$configName][$configKey] = $configValue;
    }

}