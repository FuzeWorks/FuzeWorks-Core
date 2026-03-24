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
use FuzeWorks\Core\Controller;

/**
 * Event that gets fired when a view is loaded.
 *
 * Use this to cancel the loading of a view, or change the view to be loaded
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class ViewGetEvent extends Event
{
    /**
     * The directories the view can get loaded from.
     *
     * @var array
     */
    public array $viewPaths = [];

    /**
     * The name of the view to be loaded.
     *
     * @var string|null
     */
    public ?string $viewName = null;

    /**
     * The type of view to be loaded. Eg: html, json, cli.
     *
     * @var string|null
     */
    public ?string $viewType = null;

    /**
     * The namespace of the View to be loaded. Defaults to Application\View
     *
     * @var string
     */
    public string $namespace = '\Application\View\\';

    /**
     * Arguments provided to the constructor
     *
     * @var array
     */
    public array $arguments = [];

    /**
     * @var Controller
     */
    public Controller $controller;

    public function init($viewName, $viewType, $viewPaths, $namespace, $controller, $arguments)
    {
        $this->viewName = $viewName;
        $this->viewType = $viewType;
        $this->viewPaths = $viewPaths;
        $this->namespace = $namespace;
        $this->controller = $controller;
        $this->arguments = $arguments;
    }

}