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

/**
 * A route consists of two parts: a 'routeString' and 'routeConfig'. The routeString will be matched against the provided path.
 *
 * Possible values:
 *	Default callable: Adds a route that changes the URL structure. Sends all matches to the defaultCallable router
 * 	'routingString'
 *
 * 	Custom callable: Adds a route that sends all matches to the provided callable. Allows user to replace defaultCallable
 *	'routingString' => array('callable' => array(CALLABLE))
 *
 * 	Dynamic rewrite: Adds a route that rewrites a URL to a specific controller and method configuration, using a callable. The callable can dynamically determine which page to load.
 * 	'routingString' => CALLABLE
 *
 * 	Static rewrite: Adds a route that rewrites and URL to a specific controller and method using a fixed route. This allows for pre-determined rewrites of pages.
 * 	'routingString' => ['viewType' => 'someType', 'viewName' => 'someName', 'viewMethod' => 'someMethod', 'viewParameters' => 'someParameters']
 *
 * 	Example routingString: '/^(?P<viewName>.*?)(|\/(?P<viewMethod>.*?)(|\/(?P<viewParameters>.*?)))(|\.(?P<viewType>.*?))$/'
 *  A routeString has to contain viewName, viewMethod, viewParameters and viewType in order to be processed by defaultCallable.
 */

return [];
