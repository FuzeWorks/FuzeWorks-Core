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
use Exception;
use FuzeWorks\Core\Exception\ConfiguratorException;
use FuzeWorks\Core\Exception\InvalidArgumentException;

/**
 * Class Configurator.
 *
 * The configurator prepares FuzeWorks and loads it when requested. 
 * 
 * The user passes variables into the Configurator and the Configurator makes sure
 * that FuzeWorks is loaded accordingly. 
 * 
 * This allows for more flexible startups.
 * @author    TechFuze <contact@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Configurator
{

    /**
     * The parameters that will be passed to FuzeWorks.
     *
     * @var array
     */ 
    protected array $parameters = ['debugEnabled' => false];

    /**
     * Components that have been added to FuzeWorks
     *
     * @var iComponent[]
     */
    protected array $components = [];

    /**
     * Plugins that have been added to FuzeWorks
     *
     * @var iPluginHeader[]
     */
    protected array $plugins = [];

    /**
     * Libraries that have been added to FuzeWorks
     *
     * @var iLibrary[]
     */
    protected array $libraryObjects = [];

    /**
     * Associative array of libraries to be loaded by FuzeWorks
     *
     * @var string[]
     */
    protected array $libraryClasses = [];

    /**
     * Directories that will be passed to FuzeWorks components. 
     * 
     * These are NOT the temp and log directory.
     *
     * @var array of directories
     */     
    protected array $directories = [];

    /**
     * Array of ComponentClass methods to be invoked once ComponentClass is loaded
     *
     * @var DeferredComponentClass[]
     */
    protected array $deferredComponentClassMethods = [];

    public const string COOKIE_SECRET = 'fuzeworks-debug';

    protected bool $register_error_handlers = true;

    /* ---------------- Core Directories--------------------- */

    /**
     * Sets path to temporary directory.
     *
     * @param string $path
     * @return Configurator
     * @throws InvalidArgumentException
     */
    public function setLogDirectory(string $path): Configurator
    {
        if (!is_dir($path))
            throw new InvalidArgumentException("Could not set log directory. Directory does not exist", 1);

        $this->parameters['logDir'] = $path;
        return $this;
    }

    /**
     * Sets path to temporary directory.
     *
     * @param string $path
     * @return Configurator
     * @throws InvalidArgumentException
     */
    public function setTempDirectory(string $path): Configurator
    {
        if (!is_dir($path))
            throw new InvalidArgumentException("Could not set temp directory. Directory does not exist", 1);

        $this->parameters['tempDir'] = $path;
        return $this;
    }

    /**
     * Add a directory to FuzeWorks
     *
     * @param string $directory
     * @param string $category Optional
     * @param int $priority
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addDirectory(string $directory, string $category, int $priority = Priority::NORMAL): Configurator
    {
        if (!file_exists($directory))
            throw new InvalidArgumentException("Could not add directory. Directory does not exist.");

        if (!isset($this->directories[$category]))
            $this->directories[$category] = [];

        if (!isset($this->directories[$category][$priority]))
            $this->directories[$category][$priority] = [];

        if (!in_array($directory, $this->directories[$category][$priority]))
            $this->directories[$category][$priority][] = $directory;

        return $this;
    }

    /* ---------------- Components -------------------------- */

    /**
     * Registers a component that will be added to the Factory when the container is built
     *
     * @param iComponent $component
     * @return Configurator
     */
    public function addComponent(iComponent $component): Configurator
    {
        if (isset($this->components[get_class($component)]))
            return $this;

        $component->onAddComponent($this);
        $this->components[get_class($component)] = $component;
        return $this;
    }

    /**
     * Invokes a method on a componentClass after the Container has been created.
     *
     * @param string $componentClass
     * @param string $method
     * @param callable|null $callable
     * @param   mixed    $parameters,...     Parameters for the method to be invoked
     * @return DeferredComponentClass
     */
    public function deferComponentClassMethod(string $componentClass, string $method, ?callable $callable = null): DeferredComponentClass
    {
        // Retrieve arguments
        $arguments = (func_num_args() > 3 ? array_slice(func_get_args(), 3) : []);

        // Add component
        if (!isset($this->deferredComponentClassMethods[$componentClass]))
            $this->deferredComponentClassMethods[$componentClass] = [];

        $deferredComponentClass = new DeferredComponentClass($componentClass, $method, $arguments, $callable);
        return $this->deferredComponentClassMethods[$componentClass][] = $deferredComponentClass;
    }

    /**
     * Alias for deferComponentClassMethods
     *
     * @param string $componentClass
     * @param string $method
     * @param callable|null $callable
     * @param   mixed    $parameters,...     Parameters for the method to be invoked
     * @return DeferredComponentClass
     * @codeCoverageIgnore
     */
    public function call(string $componentClass, string $method, ?callable $callable = null): DeferredComponentClass
    {
        return call_user_func_array([$this, 'deferComponentClassMethod'], func_get_args());
    }

    /* ---------------- Plugins and libraries --------------- */

    /**
     * Register a plugin that will be added to FuzeWorks after the container has been built.
     *
     * @param iPluginHeader $pluginHeader
     * @return Configurator
     */
    public function addPlugin(iPluginHeader $pluginHeader): Configurator
    {
        $className = get_class($pluginHeader);
        if (isset($this->plugins[$className]))
            return $this;

        $this->plugins[$className] = $pluginHeader;
        return $this;
    }

    /**
     * Register a library by object to be loaded after FuzeWorks container has been built
     *
     * @param iLibrary $library
     * @param string $libraryName
     * @return $this
     */
    public function addLibraryObject(iLibrary $library, string $libraryName): Configurator
    {
        if (isset($this->libraryObjects[$libraryName]))
            return $this;

        $this->libraryObjects[$libraryName] = $library;
        return $this;
    }

    /**
     * Register a library by className to be loaded after FuzeWorks container has been built
     *
     * @param string $className
     * @param string $libraryName
     * @return $this
     */
    public function addLibraryClass(string $className, string $libraryName): Configurator
    {
        if (isset($this->libraryClasses[$libraryName]))
            return $this;

        $this->libraryClasses[$libraryName] = $className;
        return $this;
    }


    /* ---------------- Other Features ---------------------- */

    /**
     * Override a config value before FuzeWorks is loaded.
     *
     * Allows the user to change any value in config files loaded by FuzeWorks.
     *
     * @param string $configFileName
     * @param string $configKey
     * @param $configValue
     * @return Configurator
     */
    public function setConfigOverride(string $configFileName, string $configKey, $configValue): Configurator
    {
        Config::overrideConfig($configFileName, $configKey, $configValue);
        return $this;
    }

    /**
     * Set the template that FuzeWorks should use to parse debug logs
     *
     * @codeCoverageIgnore
     *
     * @var string Name of the template file
     */
    public static function setLoggerTemplate($templateName)
    {
        Logger::setLoggerTemplate($templateName);
    }

    /**
     * Sets the default timezone.
     * @param string $timezone
     * @return Configurator
     * @throws InvalidArgumentException
     */
    public function setTimeZone(string $timezone): Configurator
    {
        if (!in_array($timezone, timezone_identifiers_list()))
            throw new InvalidArgumentException("Could not set timezone. Invalid timezone provided.", 1);

        @date_default_timezone_set($timezone);
        @ini_set('date.timezone', $timezone); // @ - function may be disabled

        return $this;
    }

    /**
     * Adds new parameters. Use to quickly set multiple parameters at once
     * @param array $params
     * @return Configurator
     */
    public function setParameters(array $params): Configurator
    {
        foreach ($params as $key => $value)
            $this->parameters[$key] = $value;

        return $this;
    }

    /* ---------------- Debug Mode -------------------------- */

    /**
     * Fully enable or disable debug mode using one variable
     * @return Configurator
     */
    public function enableDebugMode(): Configurator
    {
        $this->parameters['debugEnabled'] = true;
        $this->parameters['debugMatch'] = $this->parameters['debugMatch'] ?? true;
        return $this;
    }

    /**
     * Provide a string from where debug mode can be accessed.
     * Can be the following type of addresses:
     * @todo
     * @param string|array $address
     * @return Configurator
     * @throws InvalidArgumentException
     */
    public function setDebugAddress($address = 'NONE'): Configurator
    {
        // First we fetch the list
        if (!is_string($address) && !is_array($address))
            throw new InvalidArgumentException("Can not set debug address. Address must be a string or array",1);

        // Then we test some common cases
        if (is_string($address) && $address == 'NONE')
        {
            $this->parameters['debugMatch'] = false;
            return $this;
        }
        elseif (is_string($address) && $address == 'ALL')
        {
            $this->parameters['debugMatch'] = true;
            return $this;
        }

        // Otherwise, we run the regular detectDebugMode from Tracy
        $list = is_string($address)
            ? preg_split('#[,\s]+#', $address)
            : (array) $address;
        $addr = $_SERVER['REMOTE_ADDR'] ?? php_uname('n');
        $secret = isset($_COOKIE[self::COOKIE_SECRET]) && is_string($_COOKIE[self::COOKIE_SECRET])
            ? $_COOKIE[self::COOKIE_SECRET]
            : NULL;
        if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $list[] = '127.0.0.1';
            $list[] = '::1';
        }
       
        $this->parameters['debugMatch'] = in_array($addr, $list, TRUE) || in_array("$secret@$addr", $list, TRUE);
        return $this;
    }

    /**
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->parameters['debugEnabled'] && $this->parameters['debugMatch'];
    }

    /**
     * Whether errors and exceptions should be handled by the FuzeWorks Core
     * 
     * Generally advised to not touch this unless you know what you're doing
     * @param bool $enabled
     * @return void
     */
    public function registerErrorHandlers(bool $enabled = true): void
    {
        $this->register_error_handlers = $enabled;
    }

    /**
     * Create the container which holds FuzeWorks.
     *
     * Due to the static nature of FuzeWorks, this is not yet possible.
     * When issue #101 is completed, this should be resolved.
     *
     * @return Factory
     * @throws Exception
     */
    public function createContainer(): Factory
    {
        // First set all the fixed directories
        Core::$tempDir = $this->parameters['tempDir'];
        Core::$logDir = $this->parameters['logDir'];

        // Then prepare the debugger
        $debug = $this->parameters['debugEnabled'] && $this->parameters['debugMatch'];

        // Then load the framework
        $container = Core::init($this->register_error_handlers);
        Logger::newLevel("Creating container...");
        if ($debug)
        {
            define('ENVIRONMENT', 'DEVELOPMENT');
            Logger::enable();
        }
        else
            define('ENVIRONMENT', 'PRODUCTION');


        // Load components
        foreach ($this->components as $componentSuperClass => $component)
        {
            Logger::logInfo("Adding Component: '" . $component->getName() . "'");
            foreach ($component->getClasses() as $componentName => $componentClass)
            {
                if (is_object($componentClass))
                    $container->setInstance($componentName, $componentClass);
                else
                {
                    if (!class_exists($componentClass))
                        throw new ConfiguratorException("Could not load component '".$componentName."'. Class '".$componentClass."' does not exist.", 1);

                    $container->setInstance($componentName, new $componentClass());
                }
            }

            $component->onCreateContainer($container);
        }

        // Invoke deferredComponentClass on FuzeWorks\Core classes
        foreach ($this->deferredComponentClassMethods as $componentClass => $deferredComponentClasses)
        {
            if ($container->hasInstance($componentClass))
            {
                foreach ($deferredComponentClasses as $deferredComponentClass)
                {
                    Logger::logDebug("Invoking '" . $deferredComponentClass->method . "' on component '" . $deferredComponentClass->componentClass . "'");

                    /** @var DeferredComponentClass $deferredComponentClass */
                    $deferredComponentClass->invoke(call_user_func_array(
                        array($container->{$deferredComponentClass->componentClass}, $deferredComponentClass->method),
                        $deferredComponentClass->arguments
                    ));
                }
            }
        }

        // Add directories to Components
        foreach ($this->directories as $component => $priorityArray)
        {
            Logger::logDebug("Adding directories for '" . $component . "'");
            if (method_exists($container->{$component}, 'setDirectories'))
                $container->{$component}->setDirectories($priorityArray);
        }

        // Initialize and return the container
        $container->initFactory();

        // Add libraries
        foreach ($this->libraryClasses as $libraryName => $libraryClass)
            $container->libraries->addLibraryClass($libraryName, $libraryClass);

        foreach ($this->libraryObjects as $libraryName => $libraryObject)
            $container->libraries->addLibraryObject($libraryName, $libraryObject);

        // Add plugins
        foreach ($this->plugins as $className => $pluginHeader)
            $container->plugins->addPlugin($pluginHeader);

        Logger::stopLevel();
        return $container;
    }
}