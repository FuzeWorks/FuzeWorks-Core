<?php
/**
 * FuzeWorks Framework MVCR Component.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2018 TechFuze
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
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 1.2.0
 *
 * @version Version 1.3.3
 */

namespace FuzeWorks\Core;

use FuzeWorks\Core\Exception\ConfigException;
use FuzeWorks\Core\Exception\EventException;
use FuzeWorks\Core\Event\RouterCallViewEvent;
use FuzeWorks\Core\Event\RouterLoadCallableEvent;
use FuzeWorks\Core\Event\RouterLoadViewAndControllerEvent;
use FuzeWorks\Core\Exception\ControllerException;
use FuzeWorks\Core\Exception\HaltException;
use FuzeWorks\Core\Exception\NotFoundException;
use FuzeWorks\Core\Exception\RouterException;
use FuzeWorks\Core\Exception\ViewException;

class Router
{
    /**
     * The routes loaded into the Router
     *
     * @var array
     */
    protected array $routes = [];

    /**
     * The current callable used
     *
     * @var callable|null
     */
    protected $callable = null;

    /**
     * The current matches used
     *
     * @var array|null
     */
    protected ?array $matches = null;

    /**
     * @var array|Closure|null
     */
    protected $routeData = null;

    /**
     * The current route used
     *
     * @var string|null
     */
    protected ?string $route = null;

    /**
     * The current View
     *
     * @var View|null
     */
    protected ?View $view = null;

    /**
     * The current Controller
     *
     * @var Controller|null
     */
    protected ?Controller $controller = null;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Controllers
     */
    private Controllers $controllers;

    /**
     * @var Views
     */
    private Views $views;

    /**
     * Router constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        // Prepare
        $factory = Factory::getInstance();
        $this->config = $factory->config;
        $this->controllers = $factory->controllers;
        $this->views = $factory->views;
    }

    /**
     * Route Parser
     *
     * This method parses all the routes in the routes table config file
     * and adds them to the Router. It converts some routes which use wildcards
     *
     * @return void
     * @throws RouterException
     */
    public function init()
    {
        // Get routing routes
        try {
            $routes = $this->config->getConfig('routes');
            // @codeCoverageIgnoreStart
        } catch (ConfigException $e) {
            throw new RouterException("Could not parse routing. Error in config 'routes'");
            // @codeCoverageIgnoreEnd
        }

        // Cycle through all provided routes
        foreach ($routes as $route => $routeConfig)
        {
            // Check if only a string is provided
            // e.g: 0 => '.*$'
            if (is_int($route))
            {
                $route = $routeConfig;
                $routeConfig = ['callable' => [$this, 'defaultCallable']];
            }

            // Finally, add the route
            $this->addRoute($route, $routeConfig);
        }
    }

    /**
     * Add a route to the Router
     *
     * @param string $route
     * @param mixed $routeConfig
     * @param int $priority
     */
    public function addRoute(string $route, $routeConfig = null, int $priority = Priority::NORMAL)
    {
        // Set defaultCallable if no value provided
        if (is_null($routeConfig))
            $routeConfig = ['callable' => [$this, 'defaultCallable']];

        // Select the category
        $category = is_array($routeConfig) && isset($routeConfig['category']) ? $routeConfig['category'] : 'default';
        if (!isset($this->routes[$category]))
            $this->routes[$category] = [];

        // Convert wildcards to Regex
        $route = str_replace([':any',':num'], ['[^/]+', '[0-9]+'], $route);

        if (!isset($this->routes[$category][$priority]))
            $this->routes[$category][$priority] = [];

        if (!isset($this->routes[$category][$priority][$route]))
            $this->routes[$category][$priority][$route] = $routeConfig;

        Logger::log('Route added with ' . Priority::getPriority($priority) . ": '" . $route."'");
    }

    /**
     * Removes a route from the array based on the given route.
     *
     * @param string    $route      The route to remove
     * @param string    $category   The category to remove it from (defaults to 'default')
     * @param int       $priority   The priority to remove it from (defaults to Priority::NORMAL)
     */
    public function removeRoute(string $route, string $category = 'default', int $priority = Priority::NORMAL)
    {
        if (!isset($this->routes[$category][$priority][$route]))
            return;

        unset($this->routes[$category][$priority][$route]);
        Logger::log('Route removed: '.$route);
    }

    /**
     * @param string        $path           The string to route using Router
     * @param string        $category       The category of routes to search in (defaults to 'default')
     * @param array         $routes         Alternative routes if using global routes is not desired
     * @return mixed                        The output of the callable
     * @throws NotFoundException            Thrown if route failed to deliver
     * @throws RouterException              Thrown if things fatally break
     * @throws HaltException                Thrown if route results in an unauthorised request
     */
    public function route(string $path, string $category = 'default', array $routes = [])
    {
        // Select the routes to use
        $globalRoutes = $this->routes[$category] ?? [];
        $routes = empty($routes) ? $globalRoutes : $routes;

        // Check all the provided custom paths, ordered by priority
        for ($i=Priority::getHighestPriority(); $i<=Priority::getLowestPriority(); $i++) {
            if (!isset($routes[$i]))
                continue;

            foreach ($routes[$i] as $route => $routeConfig)
            {
                // Match the path against the routes
                if (!preg_match('#^'.$route.'$#', $path, $matches))
                    continue;

                // Save the matches
                Logger::log("Route matched: '" . $route . "' with " . Priority::getPriority($i));
                $this->matches = $matches;
                $this->routeData = $routeConfig;
                $this->route = $route;
                $this->callable = null;

                // Call callable if routeConfig is callable, so routeConfig can be replaced
                // This is an example of 'Dynamic Rewrite'
                // e.g: '.*$' => callable
                if (is_callable($this->routeData))
                    $this->routeData = call_user_func_array($this->routeData, [$matches]);

                // If routeConfig is an array, multiple things might be at hand
                if (is_array($this->routeData))
                {
                    // Replace defaultCallable if a custom callable is provided
                    // This is an example of 'Custom Callable'
                    // e.g: '.*$' => ['callable' => [$object, 'method']]
                    if (isset($this->routeData['callable']) && is_callable($this->routeData['callable']))
                        $this->callable = $this->routeData['callable'];

                    // If the route provides a configuration, use that
                    // This is an example of 'Static Rewrite'
                    // e.g: '.*$' => ['viewName' => 'custom', 'viewType' => 'cli', 'function' => 'index']
                    else
                        $this->matches = array_merge($this->matches, $this->routeData);
                }

                // If no custom callable is provided, use default
                // This is an example of 'Default Callable'
                if (is_null($this->callable))
                    $this->callable = [$this, 'defaultCallable'];

                // Attempt and load callable. If false, continue
                $output = $this->loadCallable($this->callable, $this->matches, $this->routeData, $route);
                if (is_bool($output) && $output === FALSE)
                {
                    Logger::log('Callable not satisfied, skipping to next callable');
                    continue;
                }

                return $output;
            }
        }

        throw new NotFoundException("Could not load view. Router could not find matching route with satisfied callable.");
    }

    /**
     * @param callable $callable
     * @param array $matches
     * @param array $routeData
     * @param string $route
     * @return mixed
     * @throws HaltException
     * @throws RouterException
     */
    protected function loadCallable(callable $callable, array $matches, array $routeData, string $route)
    {
        // Log the input to the logger
        Logger::newLevel('Loading callable with matches:');
        foreach ($matches as $key => $value) {
            if (!is_int($key))
                Logger::log($key.': '.var_export($value, true).'');
        }

        try {
            /** @var RouterLoadCallableEvent $event */
            $event = Events::fireEvent(new RouterLoadCallableEvent(),
                $callable,
                $matches,
                $routeData,
                $route
            );
        } catch (EventException $e) {
            throw new RouterException("Could not load callable. routerLoadCallableEvent threw exception: '".$e->getMessage()."'");
        }

        // Halt if cancelled
        if ($event->isCancelled())
            throw new HaltException("Will not load callable. Cancelled by routerLoadCallableEvent.");

        // Invoke callable
        $output = call_user_func_array($event->callable, [$event->matches, $event->routeData, $event->route]);
        Logger::stopLevel();
        return $output;
    }

    /**
     * @param array $matches
     * @param array $routeData
     * @param string $route
     * @return mixed
     * @throws HaltException
     * @throws RouterException
     * @todo Use $route and send it to the view
     */
    public function defaultCallable(array $matches, array $routeData, string $route)
    {
        Logger::log('defaultCallable called');

        // Prepare variables
        // Variables from matches
        $viewName = !empty($matches['viewName']) ? $matches['viewName'] : $this->config->routing->default_view;
        $viewMethod = !empty($matches['viewMethod']) ? $matches['viewMethod'] : $this->config->routing->default_viewMethod;
        $viewParameters = !empty($matches['viewParameters']) ? $matches['viewParameters'] : '';

        // Variables from routeData
        $viewType = !empty($routeData['viewType']) ? $routeData['viewType'] : $this->config->routing->default_viewType;
        $namespacePrefix = !empty($routeData['namespacePrefix']) ? $routeData['namespacePrefix'] : $this->config->routing->default_namespacePrefix;

        try {
            /** @var RouterLoadViewAndControllerEvent $event */
            $event = Events::fireEvent(new RouterLoadViewAndControllerEvent(),
                $viewName,
                $viewType,
                // ViewMethod is provided as a Priority::NORMAL method
                [3 => [$viewMethod]],
                [$viewParameters],
                $namespacePrefix,
                $route
            );
        } catch (EventException $e) {
            throw new RouterException("Could not load view. routerLoadViewAndControllerEvent threw exception: '".$e->getMessage()."'");
        }

        // Cancel if requested to do so
        if ($event->isCancelled())
            throw new HaltException("Will not load view. Cancelled by routerLoadViewAndControllerEvent.");

        // First receive the controller
        try {
            $this->controller = (!is_null($event->controller) ? $event->controller : $this->controllers->get($event->viewName, [], $event->namespacePrefix . 'Controller\\'));
        } catch (ControllerException $e) {
            throw new RouterException("Could not load view. Controllers::get threw ControllerException: '".$e->getMessage()."'");
        } catch (NotFoundException $e) {
            Logger::logError("Could not load view. Controller does not exist.");
            return false;
        }

        // Then try and receive the view
        try {
            $this->view = $this->views->get($event->viewName, $this->controller, $event->viewType, [], $event->namespacePrefix. 'View\\');
        } catch (ViewException $e) {
            throw new RouterException("Could not load view. Views::get threw ViewException: '".$e->getMessage()."'");
        } catch (NotFoundException $e) {
            Logger::logError("Could not load view. View does not exist.");
            return false;
        }

        // Fire routerCallViewEvent
        try {
            /** @var RouterCallViewEvent $event */
            $event = Events::fireEvent(new RouterCallViewEvent(),
                $this->view,
                $this->controller,
                $event->viewMethods,
                $event->viewParameters,
                $event->route
            );

            // Reset vars
            $this->view = $event->view;
            if ($this->controller !== $event->controller)
            {
                $this->controller = $event->controller;
                $this->view->setController($this->controller);
            }
        } catch (EventException $e) {
            throw new RouterException("Could not load view. routerCallViewEvent threw exception: '".$e->getMessage()."'");
        }

        // Cancel if requested to do so
        if ($event->isCancelled())
            throw new HaltException("Will not load view. Cancelled by routerCallViewEvent");

        // If the view does not want a function to be loaded, provide a halt parameter
        if (isset($this->view->halt))
            throw new HaltException("Will not load view. Cancelled by 'halt' attribute in view.");

        // Cycle over every viewMethod until a valid one is found
        for ($i=Priority::getHighestPriority(); $i<=Priority::getLowestPriority(); $i++) {
            if (!isset($event->viewMethods[$i]))
                continue;

            foreach ($event->viewMethods[$i] as $method) {
                if (method_exists($this->view, $method))
                {
                    // Execute this method on the view
                    Logger::newLevel("Calling method '$method' on " . get_class($this->view) . ' with ' . get_class($this->controller));
                    $output = call_user_func_array([$this->view, $method], $event->viewParameters);
                    Logger::stopLevel();
                    return $output;
                }
            }
        }

        // Otherwise log an error
        Logger::logError("Could not load view. View does not have any of the provided methods.");

        // View could not be found.
        return false;
    }

    /**
     * Returns an array with all the routes.
     *
     * @param string $category
     * @param int $priority
     * @return array
     * @codeCoverageIgnore
     */
    public function getRoutes(string $category = 'default', int $priority = Priority::NORMAL): array
    {
        if (isset($this->routes[$category][$priority]))
            return $this->routes[$category][$priority];

        return [];
    }

    /**
     * Returns the current route
     *
     * @return string|null
     * @codeCoverageIgnore
     */
    public function getCurrentRoute(): ?string
    {
        return $this->route;
    }

    /**
     * Returns all the matches with the RegEx route.
     *
     * @return null|array
     * @codeCoverageIgnore
     */
    public function getCurrentMatches(): ?array
    {
        return $this->matches;
    }

    /**
     * Returns the current View
     *
     * @return View|null
     * @codeCoverageIgnore
     */
    public function getCurrentView(): ?View
    {
        return $this->view;
    }

    /**
     * Returns the current Controller
     *
     * @return Controller|null
     * @codeCoverageIgnore
     */
    public function getCurrentController(): ?Controller
    {
        return $this->controller;
    }
}