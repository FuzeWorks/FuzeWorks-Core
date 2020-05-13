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
use FuzeWorks\Exception\ConfiguratorException;
use FuzeWorks\Exception\InvalidArgumentException;

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
    protected $parameters = ['debugEnabled' => false];

    /**
     * Components that have been added to FuzeWorks
     *
     * @var iComponent[]
     */
    protected $components = [];

    /**
     * Directories that will be passed to FuzeWorks components. 
     * 
     * These are NOT the temp and log directory.
     *
     * @var array of directories
     */     
    protected $directories = [];

    /**
     * Array of ComponentClass methods to be invoked once ComponentClass is loaded
     *
     * @var DeferredComponentClass[]
     */
    protected $deferredComponentClassMethods = [];

    const COOKIE_SECRET = 'fuzeworks-debug';

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
    public function addDirectory(string $directory, string $category, $priority = Priority::NORMAL): Configurator
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
    public function deferComponentClassMethod(string $componentClass, string $method, callable $callable = null)
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
    public function call(string $componentClass, string $method, callable $callable = null)
    {
        return call_user_func_array([$this, 'deferComponentClassMethod'], func_get_args());
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
        foreach ($params as $key => $value) {
            $this->parameters[$key] = $value;
        }

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
        $this->parameters['debugMatch'] = (isset($this->parameters['debugMatch']) ? $this->parameters['debugMatch'] : true);

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

        // Otherwise we run the regular detectDebugMode from Tracy
        $list = is_string($address)
            ? preg_split('#[,\s]+#', $address)
            : (array) $address;
        $addr = isset($_SERVER['REMOTE_ADDR'])
            ? $_SERVER['REMOTE_ADDR']
            : php_uname('n');
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
     * Create the container which holds FuzeWorks.
     *
     * Due to the static nature of FuzeWorks, this is not yet possible.
     * When issue #101 is completed, this should be resolved.
     *
     * @return Factory
     * @throws \Exception
     */
    public function createContainer(): Factory
    {
        // First set all the fixed directories
        Core::$tempDir = $this->parameters['tempDir'];
        Core::$logDir = $this->parameters['logDir'];

        // Then prepare the debugger
        $debug = ($this->parameters['debugEnabled'] && $this->parameters['debugMatch'] ? true : false);

        // Then load the framework
        $container = Core::init();
        Logger::newLevel("Creating container...");
        if ($debug == true)
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
                {
                    $container->setInstance($componentName, $componentClass);
                }
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
            if ($container->instanceIsset($componentClass))
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

        $container->initFactory();
        Logger::stopLevel();
        return $container;
    }
}