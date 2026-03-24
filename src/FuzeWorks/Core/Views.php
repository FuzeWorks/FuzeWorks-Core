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

use FuzeWorks\Core\Event\ViewGetEvent;
use FuzeWorks\Core\Exception\NotFoundException;
use FuzeWorks\Core\Exception\ViewException;
use FuzeWorks\Core\Exception\EventException;

/**
 * Views Class.
 *
 * Simple loader class for MVC Views.
 * Typically, loads views from Application\View unless otherwise specified.
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Views
{
    use ComponentPathsTrait;

    /**
     * Get a view.
     *
     * Supply the name and the view will be loaded from the supplied directory,
     * or from one of the viewPaths (which you can add).
     *
     * @param string        $viewName      Name of the view
     * @param Controller    $controller
     * @param string        $viewType       The type of view to be loaded. Defaults to 'Standard'
     * @param array         $viewPaths      Alternative paths to use to load the view
     * @param string        $namespace      Alternative namespace for the view. Defaults to \Application\View
     * @param mixed         %arguments,...  Arguments to be provided to the constructor [...]
     * @return View
     * @throws NotFoundException
     * @throws ViewException
     */
    public function get(string $viewName, Controller $controller, string $viewType = 'Standard', array $viewPaths = [], string $namespace = '\Application\View\\'): View
    {
        if (empty($viewName))
            throw new ViewException("Could not load view. No name provided", 1);

        // First get the directories where the view can be located
        $viewPaths = (empty($viewPaths) ? $this->componentPaths : [3 => $viewPaths]);

        // Get arguments for constructor
        $arguments = func_num_args() > 5 ? array_slice(func_get_args(), 5) : [];

        // Fire a viewGetEvent
        try {
            /** @var ViewGetEvent $event */
            $event = Events::fireEvent(new ViewGetEvent(), $viewName, $viewType, $viewPaths, $namespace, $controller, $arguments);
        } catch (EventException $e) {
            throw new ViewException("Could not load view. viewGetEvent threw exception: '".$e->getMessage()."''");
        }

        // If the event is cancelled, stop loading
        if ($event->isCancelled())
            throw new ViewException("Could not load view. View cancelled by viewGetEvent.");

        // And attempt to load the view
        return $this->loadView($event->viewName, $event->controller, $event->viewType, $event->viewPaths, $event->namespace, $event->arguments);
    }

    /**
     * Load and return a view.
     *
     * Supply the name and the view will be loaded from one of the supplied directories
     *
     * @param string $viewName Name of the view
     * @param Controller $controller
     * @param string $viewType Type of the view
     * @param array $viewPaths
     * @param string $namespace
     * @param array $arguments
     * @return View                 The View object
     * @throws NotFoundException
     * @throws ViewException
     */
    protected function loadView(string $viewName, Controller $controller, string $viewType, array $viewPaths, string $namespace, array $arguments): View
    {
        // Now figure out the className and subdir
        $class = trim($viewName, '/');
        if (($last_slash = strrpos($class, '/')) !== FALSE) {
            // Extract the path
            $subdir = substr($class, 0, ++$last_slash);

            // Get the filename from the path
            $class = substr($class, $last_slash);
        } else {
            $subdir = '';
        }

        // If the class already exists, return a new instance directly
        $class = ucfirst($class);
        $className = $namespace . $class . ucfirst($viewType) . 'View';
        if (class_exists($className, true)) {
            /** @var View $view */
            $view = new $className(...$arguments);
            if (!$view instanceof  View)
                throw new ViewException("Could not load view. Provided viewName is not instance of \FuzeWorks\View");

            // Load and return
            $view->setController($controller);
            return $view;
        }

        // Search for the view file
        for ($i=Priority::getHighestPriority(); $i<=Priority::getLowestPriority(); $i++)
        {
            if (!isset($viewPaths[$i]))
                continue;

            foreach ($viewPaths[$i] as $directory) {

                // Determine the file
                $file = $directory . DS . $subdir . "view." . strtolower($viewType) . "." . strtolower($class) . '.php';

                // If it doesn't, try and load the file
                if (file_exists($file)) {
                    include_once($file);

                    /** @var View $view */
                    $view = new $className(...$arguments);
                    if (!$view instanceof  View)
                        throw new ViewException("Could not load view. Provided viewName is not instance of \FuzeWorks\View");

                    // Load and return
                    $view->setController($controller);
                    return $view;
                }
            }
        }

        // Maybe it's in a subdirectory with the same name as the class
        if ($subdir === '') {
            return $this->loadView($class . "/" . $class, $controller, $viewType, $viewPaths, $namespace, $arguments);
        }

        throw new NotFoundException("Could not load view. View was not found", 1);
    }
}