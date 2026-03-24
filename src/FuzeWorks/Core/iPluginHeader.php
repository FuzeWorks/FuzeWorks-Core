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
 * @since Version 1.1.4
 *
 * @version Version 1.2.0
 */

namespace FuzeWorks\Core;


interface iPluginHeader
{
    /**
     * Should return the name of the plugin.
     *
     * This is the name used to access the plugin when using Plugins::get()
     * @return string
     */
    public function getName(): string;

    /**
     * Should return the namespace prefix of the classes of this plugin.
     *
     * Used to autoload classes of this plugin.
     * @see https://www.php-fig.org/psr/psr-4/
     *
     * Invoked upon `FuzeWorks\Plugins::get`. Autoloading plugin classes before that is not possible.
     *
     * @return string|null
     */
    public function getClassesPrefix(): ?string;

    /**
     * Should return the directory where the classes of this plugin can be found.
     *
     * Only the source directory within the plugin should be returned, e.g:
     * If the source directory is 'src' within the plugin directory, return 'src'
     *
     * @return string|null
     */
    public function getSourceDirectory(): ?string;

    /**
     * Should return the className of the main class of this plugin
     *
     * Should only return null if the pluginHeader has the optional getPlugin() method.
     *
     * @return string|null
     */
    public function getPluginClass(): ?string;

    /**
     * Initializes the pluginHeader. This method allows the developer to hook into multiple systems of FuzeWorks
     * upon FuzeWorks initialization. See the FuzeWorks boot process for more information.
     *
     * @return mixed
     */
    public function init();
}