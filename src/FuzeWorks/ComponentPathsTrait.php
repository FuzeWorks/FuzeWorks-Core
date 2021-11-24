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
 * @since Version 1.2.0
 *
 * @version Version 1.3.0
 */

namespace FuzeWorks;


trait ComponentPathsTrait
{

    /**
     * Array of all the paths where objects for this component can be found
     *
     * @var array $componentPaths
     */
    protected array $componentPaths = [];

    /**
     * Set the directories. Automatically gets invoked if componentPaths are added by FuzeWorks\Configurator.
     *
     * @param array $componentPaths
     */
    public function setDirectories(array $componentPaths)
    {
        $this->componentPaths = $componentPaths;
    }

    /**
     * Add a path where objects for this component can be found
     *
     * @param string $componentPath
     * @param int $priority
     */
    public function addComponentPath(string $componentPath, int $priority = Priority::NORMAL)
    {
        if (!isset($this->componentPaths[$priority]))
            $this->componentPaths[$priority] = [];

        if (!in_array($componentPath, $this->componentPaths[$priority]))
            $this->componentPaths[$priority][] = $componentPath;
    }

    /**
     * Remove a path where objects for this component can be found
     *
     * @param string $componentPath
     * @param int $priority
     */
    public function removeComponentPath(string $componentPath, int $priority = Priority::NORMAL)
    {
        if (!isset($this->componentPaths[$priority]))
            return;

        if (($key = array_search($componentPath, $this->componentPaths[$priority])) !== false)
            unset($this->componentPaths[$priority][$key]);
    }

    /**
     * Get a list of all current componentPaths
     *
     * @param int $priority
     * @return array of paths where objects for this component can be found
     */
    public function getComponentPaths(int $priority = Priority::NORMAL): array
    {
        return $this->componentPaths[$priority] ?? [];
    }
}