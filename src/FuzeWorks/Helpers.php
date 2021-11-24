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
 * @version Version 1.3.0
 */

namespace FuzeWorks;
use FuzeWorks\Event\HelperLoadEvent;
use FuzeWorks\Exception\EventException;
use FuzeWorks\Exception\HelperException;

/**
 * Helpers Class.
 *
 * Helpers, as the name suggests, help you with tasks. 
 * 
 * Each helper file is simply a collection of functions in a particular category.
 * There are URL Helpers, that assist in creating links, there are Form Helpers that help you create form elements, 
 * Text Helpers perform various text formatting routines, Cookie Helpers set and read cookies, 
 * File Helpers help you deal with files, etc.
 *
 * Unlike most other systems in FuzeWorks, Helpers are not written in an Object-Oriented format.
 * They are simple, procedural functions. Each helper function performs one specific task, with no dependence on other functions.
 *
 * FuzeWorks does not load Helper Files by default, so the first step in using a Helper is to load it. Once loaded, 
 * it becomes globally available to everything. 
 *
 * @author    TechFuze <contact@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Helpers
{
    use ComponentPathsTrait;

    /**
     * Array of loadedHelpers, so that they won't be reloaded
     * 
     * @var array Array of loaded helperNames
     */
    protected array $helpers = [];

    /**
     * Load a helper.
     *
     * Supply the name and the helper will be loaded from the supplied directory,
     * or from one of the helperPaths (which you can add).
     *
     * @param string $helperName Name of the helper
     * @param array $helperPaths
     * @return bool                     Whether the helper was successfully loaded (true if yes)
     * @throws HelperException
     */
    public function load(string $helperName, array $helperPaths = []): bool
    {
        // Determine what directories should be checked
        $helperPaths = (empty($helperPaths) ? $this->componentPaths : [3 => $helperPaths]);

        // Check it is already loaded
        if (isset($this->helpers[$helperName]))
        {
            Logger::log("Helper '".$helperName."' is already loaded. Skipping");
            return false;
        }

        /** @var HelperLoadEvent $event */
        try {
            $event = Events::fireEvent('helperLoadEvent', $helperName, $helperPaths);

            // @codeCoverageIgnoreStart
        } catch (EventException $e) {
            throw new HelperException("Could not load helper. helperLoadEvent failed: '" . $e->getMessage() . "''");
            // @codeCoverageIgnoreEnd
        }

        // If cancelled by event, abort loading helper
        if ($event->isCancelled())
        {
            Logger::log("Not loading helper. Aborted by event");
            return false;
        }

        // Iterate over helperPaths and attempt to load if helper exists
        for ($i=Priority::getHighestPriority(); $i<=Priority::getLowestPriority(); $i++)
        {
            if (!isset($event->helperPaths[$i]))
                continue;

            foreach ($event->helperPaths[$i] as $helperPath)
            {
                $file = $helperPath . DS . $event->helperName . '.php';
                $subfile = $helperPath . DS . $event->helperName . DS . $event->helperName . '.php';
                if (file_exists($file))
                {
                    // Load and register
                    include_once($file);
                    $this->helpers[$event->helperName] = true;
                    Logger::log("Loaded helper '".$event->helperName."'");
                    return true;
                }

                // If php file not in main directory, check subdirectories
                elseif (file_exists($subfile))
                {
                    // Load and register
                    include_once($subfile);
                    $this->helpers[$event->helperName] = true;
                    Logger::log("Loaded helper '".$event->helperName."''");
                    return true;
                }
            }
        }

        throw new HelperException("Could not load helper. Helper not found.", 1);
    }

    /**
     * Alias for load
     * @param string $helperName Name of the helper
     * @param array $helperPaths
     * @return bool                     Whether the helper was successfully loaded (true if yes)
     * @throws HelperException
     *@see load() for more details
     *
     */
    public function get(string $helperName, array $helperPaths = []): bool
    {
        return $this->load($helperName, $helperPaths);
    }
}