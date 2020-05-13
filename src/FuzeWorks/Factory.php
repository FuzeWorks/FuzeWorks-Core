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
 * @version Version 1.2.0
 */

namespace FuzeWorks;
use FuzeWorks\Exception\ConfigException;
use FuzeWorks\Exception\CoreException;
use FuzeWorks\Exception\EventException;
use FuzeWorks\Exception\FactoryException;

/**
 * Factory Class.
 * 
 * The Factory class is the central point for class communication in FuzeWorks. 
 * When someone needs to load, for instance, the layout class, one has to do the following:
 * $factory = Factory::getInstance();
 * $layout = $factory->layout;
 * 
 * The Factory class allows the user to replace dependencies on the fly. It is possible for a class
 * to replace a dependency, like Logger, on the fly by calling the $factory->newInstance('Logger'); or the
 * $factory->setInstance('Logger', $object); This allows for creative ways to do dependency injection, or keep classes
 * separated. 
 * 
 * It is also possible to load a cloned instance of the Factory class, so that all properties are independant as well,
 * all to suit your very needs.
 * 
 * The Factory class is also extendible. This allows classes that extend Factory to access all it's properties. 
 * 
 * @author    TechFuze <contact@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Factory
{

	/**
	 * The Factory instance that is shared by default when calling Factory::getInstance();
	 * 
	 * @var Factory Default shared instance
	 */
	private static $sharedFactoryInstance;

    /**
     * Whether the Factory has been initialized or not
     *
     * @var bool $initialized
     */
	private $initialized = false;

	/**
	 * Config Object
	 * @var Config
	 */
	public $config;
	
	/**
	 * Logger Object
	 * @var Logger
	 */
	public $logger;
	
	/**
	 * Events Object
	 * @var Events
	 */
	public $events;
	
	/**
	 * Libraries Object
	 * @var Libraries
	 */
	public $libraries;
	
	/**
	 * Helpers Object
	 * @var Helpers
	 */
	public $helpers;
	
	/**
	 * Plugins Object
	 * @var Plugins
	 */
	public $plugins;

    /**
     * Factory instance constructor. Should only really be called once
     * @throws ConfigException
     * @throws FactoryException
     */
	public function __construct()
	{
		// If there is no sharedFactoryInstance, prepare it
		if (is_null(self::$sharedFactoryInstance))
		{
			// @codeCoverageIgnoreStart
			self::$sharedFactoryInstance = $this;
	        $this->config = new Config();
	        $this->logger = new Logger();
	        $this->events = new Events();
	        $this->libraries = new Libraries();
	        $this->helpers = new Helpers();
	        $this->plugins = new Plugins();

	        return;
		}
		// @codeCoverageIgnoreEnd

		// Otherwise, copy the existing instances
		$x = self::getInstance();
		foreach ($x as $key => $value)
		{
		    $this->{$key} = $value;
		}

		return;
	}

    /**
     * Finalizes the Factory and sends out a coreStartEvent
     *
     * @return Factory
     * @throws CoreException
     */
	public function initFactory(): Factory
    {
        // If already initialized, cancel
        if ($this->initialized)
            return $this;

        // Load the config file of the FuzeWorks core
        try {
            $cfg = $this->config->get('core');
        } catch (ConfigException $e) {
            throw new CoreException("Could not initiate Factory. Config 'core' could not be found.");
        }

        // Disable events if requested to do so
        if (!$cfg->get('enable_events'))
            Events::disable();

        // Initialize all components
        foreach ($this as $component)
        {
            if (method_exists($component, 'init'))
                $component->init();
        }

        // Initialize all plugins
        $this->plugins->loadHeadersFromPluginPaths();

        // Log actions
        Logger::logInfo("FuzeWorks initialized. Firing coreStartEvent.");

        // And fire the coreStartEvent
        try {
            Events::fireEvent('coreStartEvent');
        } catch (EventException $e) {
            throw new CoreException("Could not initiate Factory. coreStartEvent threw exception: ".$e->getMessage());
        }

        return $this;
    }

    /**
     * Get an instance of a componentClass.
     *
     * @param string|null $instanceName
     * @return mixed
     * @throws FactoryException
     */
	public static function getInstance(string $instanceName = null)
    {
	    if (is_null($instanceName))
	        return self::$sharedFactoryInstance;

        // Determine the instance name
        $instanceName = strtolower($instanceName);

	    if (!isset(self::$sharedFactoryInstance->{$instanceName}))
	        throw new FactoryException("Could not get instance. Instance was not found.");

	    return self::$sharedFactoryInstance->{$instanceName};
	}

    /**
     * Create a new instance of one of the loaded classes.
     * It reloads the class. It does NOT clone it.
     *
     * @param string $className The name of the loaded class, WITHOUT the namespace
     * @param string $namespace Optional namespace. Defaults to 'FuzeWorks\'
     * @return Factory Instance
     * @throws FactoryException
     */
	public function newInstance($className, $namespace = 'FuzeWorks\\'): self
	{
		// Determine the class to load
		$instanceName = strtolower($className);
		$className = $namespace.ucfirst($className);

		if (!isset($this->{$instanceName}))
		{
			throw new FactoryException("Could not load new instance of '".$instanceName."'. Instance was not found.", 1);
		}
		elseif (!class_exists($className, false))
		{
			throw new FactoryException("Could not load new instance of '".$instanceName."'. Class not found.", 1);
		}

		// Remove the current instance
		unset($this->{$instanceName});

		// And set the new one
		$this->{$instanceName} = new $className();

		// Return itself
		return $this;
	}

    /**
     * Clone an instance of one of the loaded classes.
     * It clones the class. It does NOT re-create it.
     *
     * If the $onlyReturn = true is provided, the cloned instance will only be returned, and not set to the factory.
     *
     * @param string $className The name of the loaded class, WITHOUT the namespace
     * @param bool $onlyReturn
     * @return mixed
     * @throws FactoryException
     */
	public static function cloneInstance(string $className, bool $onlyReturn = false)
	{
		// Determine the class to load
		$instanceName = strtolower($className);

		if (!isset(self::$sharedFactoryInstance->{$instanceName}))
			throw new FactoryException("Could not clone instance of '".$instanceName."'. Instance was not found.", 1);

		if ($onlyReturn)
		    return clone self::$sharedFactoryInstance->{$instanceName};

		// Clone the instance
		self::$sharedFactoryInstance->{$instanceName} = clone self::$sharedFactoryInstance->{$instanceName};

		// Return itself
		return self::$sharedFactoryInstance->{$instanceName};
	}

	/**
	 * Set an instance of one of the loaded classes with your own $object.
	 * Replace the existing class with one of your own.
	 * 
	 * @param string $objectName The name of the loaded class, WITHOUT the namespace
	 * @param mixed  $object    Object to replace the class with
	 * @return Factory Instance
	 */
	public function setInstance($objectName, $object): self
	{
		// Determine the instance name
		$instanceName = strtolower($objectName);

		// Unset and set
		unset($this->{$instanceName});
		$this->{$instanceName} = $object;

		// Return itself
		return $this;
	}

    /**
     * Remove an instance of one of the loaded classes.
     *
     * @param string $className The name of the loaded class, WITHOUT the namespace
     * @return Factory Factory Instance
     * @throws FactoryException
     */
	public function removeInstance($className): self
	{
		// Determine the instance name
		$instanceName = strtolower($className);

		if (!isset($this->{$instanceName}))
		{
			throw new FactoryException("Could not remove instance of '".$instanceName."'. Instance was not found.", 1);
		}

		// Unset
		unset($this->{$instanceName});

		// Return itself
		return $this;
	}

    /**
     * Returns true if component is part of this Factory.
     *
     * @param $componentName
     * @return bool
     */
    public function instanceIsset($componentName)
    {
        return isset($this->{$componentName});
    }
}
