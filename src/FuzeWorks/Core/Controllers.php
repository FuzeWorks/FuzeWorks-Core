<?php
/**
 * FuzeWorks Framework MVCR Component.
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
 * @since Version 1.2.0
 *
 * @version Version 1.3.3
 */

namespace FuzeWorks\Core;

use FuzeWorks\Core\Exception\EventException;
use FuzeWorks\Core\Event\ControllerGetEvent;
use FuzeWorks\Core\Exception\ControllerException;
use FuzeWorks\Core\Exception\NotFoundException;

/**
 * Controllers Class.
 *
 * Simple loader class for MVC Controllers.
 * Typically, loads controllers from Application\Controller unless otherwise specified.
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Controllers
{
    use ComponentPathsTrait;

    /**
     * Get a controller.
     *
     * Supply the name and the controller will be loaded from the supplied directory,
     * or from one of the controllerPaths (which you can add).
     *
     * @param string        $controllerName     Name of the controller
     * @param array         $controllerPaths    Alternative paths to use to load the controller
     * @param string        $namespace          Alternative namespace for the controller. Defaults to \Application\Controller
     * @param mixed         %arguments,...      Arguments to be provided to the constructor [...]
     * @return Controller
     * @throws ControllerException
     * @throws NotFoundException
     */
    public function get(string $controllerName, array $controllerPaths = [], string $namespace = '\Application\Controller\\'): Controller
    {
        if (empty($controllerName))
            throw new ControllerException("Could not load controller. No name provided", 1);

        // First get the directories where the controller can be located
        $controllerPaths = (empty($controllerPaths) ? $this->componentPaths : [3 => $controllerPaths]);

        // Get arguments for constructor
        $arguments = func_num_args() > 3 ? array_slice(func_get_args(), 3) : [];

        // Fire a controller load event
        try {
            /** @var ControllerGetEvent $event */
            $event = Events::fireEvent(new ControllerGetEvent(), $controllerName, $controllerPaths, $namespace, $arguments);
        } catch (EventException $e) {
            throw new ControllerException("Could not load controller. controllerGetEvent threw exception: '".$e->getMessage()."'");
        }

        // If the event is cancelled, stop loading
        if ($event->isCancelled())
            throw new ControllerException("Could not load controller. Controller cancelled by controllerGetEvent.");

        // And attempt to load the controller
        return $this->loadController($event->controllerName, $event->controllerPaths, $event->namespace, $event->arguments);
    }

    /**
     * Load and return a controller.
     *
     * Supply the name and the controller will be loaded from one of the supplied directories
     *
     * @param string $controllerName Name of the controller
     * @param array $controllerPaths
     * @param string $namespace
     * @param array $arguments
     * @return Controller                 The Controller object
     * @throws ControllerException
     * @throws NotFoundException
     */
    protected function loadController(string $controllerName, array $controllerPaths, string $namespace, array $arguments): Controller
    {
        // Now figure out the className and subdir
        $class = trim($controllerName, '/');
        if (($last_slash = strrpos($class, '/')) !== false) {
            // Extract the path
            $subdir = substr($class, 0, ++$last_slash);

            // Get the filename from the path
            $class = substr($class, $last_slash);
        } else {
            $subdir = '';
        }

        // If the class already exists, return a new instance directly
        $class = ucfirst($class);
        $className = $namespace . $class . 'Controller';
        if (class_exists($className, true))
        {
            $controller = new $className(...$arguments);
            if (!$controller instanceof Controller)
                throw new ControllerException("Could not load controller. Provided controllerName is not instance of \FuzeWorks\Controller");

            return $controller;
        }

        // Search for the controller file
        for ($i=Priority::getHighestPriority(); $i<=Priority::getLowestPriority(); $i++)
        {
            if (!isset($controllerPaths[$i]))
                continue;

            foreach ($controllerPaths[$i] as $directory) {

                // Determine the file
                $file = $directory . DS . $subdir . "controller." . strtolower($class) . '.php';

                // If it doesn't, try and load the file
                if (file_exists($file)) {
                    include_once($file);

                    // Test if provided class is instance of Controller
                    $controller = new $className(...$arguments);
                    if (!$controller instanceof Controller)
                        throw new ControllerException("Could not load controller. Provided controllerName is not instance of \FuzeWorks\Controller");

                    return $controller;
                }
            }
        }

        // Maybe it's in a subdirectory with the same name as the class
        if ($subdir === '') {
            return $this->loadController($class . "/" . $class, $controllerPaths, $namespace, $arguments);
        }

        throw new NotFoundException("Could not load controller. Controller was not found", 1);
    }
}