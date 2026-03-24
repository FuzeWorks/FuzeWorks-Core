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

namespace FuzeWorks\Core\Event;
use FuzeWorks\Core\Event;
use FuzeWorks\Core\Priority;
use FuzeWorks\Core\Controller;

/**
 * Event that gets fired when a view and controller are loaded.
 *
 * Use this to cancel the loading of a combination, or change the details of what is loaded.
 *
 * Currently, only used by Router::defaultCallable();
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class RouterLoadViewAndControllerEvent extends Event
{
    /**
     * The name of the view
     *
     * @var string
     */
    public string $viewName;

    /**
     * The type of view to be loaded
     *
     * @var string
     */
    public string $viewType;

    /**
     * The function that will be loaded in the view
     *
     * @var array
     */
    public array $viewMethods;

    /**
     * The parameters that will be provided to the function in the view
     *
     * @var array
     */
    public array $viewParameters;

    /**
     * The namespace to use to load the View and Controller
     *
     * @var string
     */
    public string $namespacePrefix;

    /**
     * The route that resulted in this controller and view
     *
     * @var string
     */
    public string $route;

    /**
     * A controller to be injected.
     *
     * @var Controller|null
     */
    public ?Controller $controller = null;

    public function init(string $viewName, string $viewType, array $viewMethods, array $viewParameters, string $namespacePrefix, string $route)
    {
        $this->viewName = $viewName;
        $this->viewType = $viewType;
        $this->viewMethods = $viewMethods;
        $this->viewParameters = $viewParameters;
        $this->namespacePrefix = $namespacePrefix;
        $this->route = $route;
    }

    /**
     * Add a method which should be tried upon calling the view
     *
     * @param string $method
     * @param int $priority
     */
    public function addMethod(string $method, int $priority = Priority::NORMAL)
    {
        if (!isset($this->viewMethods[$priority]))
            $this->viewMethods[$priority] = [];

        if (!isset($this->viewMethods[$priority][$method]))
            $this->viewMethods[$priority][] = $method;
    }

    /**
     * Add a parameter to call the view with
     *
     * @param $parameter
     */
    public function addParameter($parameter)
    {
        $this->viewParameters[] = $parameter;
    }

    /**
     * Override the controller to be provided to the view.
     *
     * @param Controller $controller
     */
    public function overrideController(Controller $controller)
    {
        $this->controller = $controller;
    }
}
