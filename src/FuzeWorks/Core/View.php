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

/**
 * Abstract class View.
 *
 * Extends all views to use useful classes
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
abstract class View
{

    /**
     * @var Controllers
     */
    protected Controllers $controllers;

    /**
     * @var Views
     */
    protected Views $views;

    /**
     * @var Plugins
     */
    protected Plugins $plugins;

    /**
     * @var Libraries
     */
    protected Libraries $libraries;

    /**
     * @var Helpers
     */
    protected Helpers $helpers;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * The controller associated with this view
     *
     * @var Controller
     */
    protected Controller $controller;

    /**
     * Provide the View with its associated Controller
     *
     * @param Controller $controller
     */
    public function setController(Controller $controller)
    {
        $this->controller = $controller;
    }

    public function __construct()
    {
        $this->controllers = Factory::getInstance()->controllers;
        $this->views = Factory::getInstance()->views;
        $this->plugins = Factory::getInstance()->plugins;
        $this->libraries = Factory::getInstance()->libraries;
        $this->helpers = Factory::getInstance()->helpers;
        $this->config = Factory::getInstance()->config;
    }

}