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
 * @version Version 1.2.0
 */

namespace FuzeWorks;
use FuzeWorks\Event\NotifierEvent;
use FuzeWorks\Exception\EventException;

/**
 * Class Events.
 *
 * FuzeWorks is built in a way that almost every core-event can be manipulated. This class provides various ways to hook into the core or other components
 * and manipulate the outcome of the functions. Components and core actions can 'fire' an event and components can 'hook' into that event. Let's take a look at the example below:
 *
 * If we want to add the current time at the end of each page title, we need to hook to the corresponding event. Those events are found in the 'events' directory in the system directory.
 * The event that will be fired when the title is changing is called layoutSetTitleEvent. So if we want our module to hook to that event, we add the following to the constructor:
 *
 * Events::addListener(array($this, "onLayoutSetTitleEvent"), "layoutSetTitleEvent", Priority::NORMAL);
 *
 * This will add the function "onLayoutSetTitleEvent" of our current class ($this) to the list of listeners with priority NORMAL. So we need to add
 * a method called onLayoutSetTitleEvent($event) it is very important to add the pointer-reference (&) or return the event, otherwise it doesn't change the event variables.
 *
 * If we now add the following code to our method, it will add the current time at the front of each title.
 *
 * $event->title = date('H:i:s ').$event->title;
 *
 * @author    TechFuze <contact@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Events
{
    /**
     * Array of classes that can handle events.
     *
     * @var array
     */
    public static $listeners = array();

    /**
     * Whether the event system is enabled or not.
     *
     * @var array
     */
    private static $enabled = true;

    /**
     * Adds a function as listener.
     *
     * @param   callable $callback           The callback when the events get fired, see {@link http://php.net/manual/en/language.types.callable.php PHP.net}
     * @param   string   $eventName          The name of the event
     * @param   int      $priority           The priority, even though integers are valid, please use Priority (for example Priority::Lowest)
     * @param   mixed    $parameters,...     Parameters for the listener
     *
     * @see Priority
     *
     * @throws EventException
     */
    public static function addListener(callable $callback, string $eventName, int $priority = Priority::NORMAL)
    {
        // Perform multiple checks
        if (Priority::getPriority($priority) == false) {
            throw new EventException('Can not add listener: Unknown priority '.$priority, 1);
        }

        if (empty($eventName))
        {
            throw new EventException("Can not add listener: No eventname provided", 1);
        }

        if (!isset(self::$listeners[$eventName])) {
            self::$listeners[$eventName] = array();
        }

        if (!isset(self::$listeners[$eventName][$priority])) {
            self::$listeners[$eventName][$priority] = array();
        }

        if (func_num_args() > 3) {
            $args = array_slice(func_get_args(), 3);
        }
        else
        {
            $args = array();
        }

        self::$listeners[$eventName][$priority][] = array($callback, $args);
    }

    /**
     * Removes a function as listener.
     *
     * @param mixed callback The callback when the events get fired, see {@link http://php.net/manual/en/language.types.callable.php PHP.net}
     * @param string $eventName The name of the event
     * @param int    $priority  The priority, even though integers are valid, please use Priority (for example Priority::Lowest)
     *
     * @see Priority
     *
     * @throws EventException
     */
    public static function removeListener(callable $callback, string $eventName, $priority = Priority::NORMAL)
    {
        if (Priority::getPriority($priority) == false) {
            throw new EventException('Unknown priority '.$priority);
        }

        if (!isset(self::$listeners[$eventName]) || !isset(self::$listeners[$eventName][$priority])) {
            return;
        }

        foreach (self::$listeners[$eventName][$priority] as $i => $_callback) {
            if ($_callback[0] == $callback) {
                unset(self::$listeners[$eventName][$priority][$i]);

                return;
            }
        }
    }

    /**
     * Fires an Event.
     *
     * The Event gets created, passed around and then returned to the issuer.
     *
     * @param   mixed $input Object for direct event, string for system event or notifierEvent
     * @param   mixed $parameters,... Parameters for the event
     *
     * @return Event The Event
     * @throws EventException
     */
    public static function fireEvent($input): Event
    {
        // First try and see if the object is an Event
        if (is_object($input))
        {
            $eventName = get_class($input);
            $eventName = explode('\\', $eventName);
            $eventName = end($eventName);
            $event = $input;
        }
        // Otherwise try to load an event based on the input string
        elseif (is_string($input))
        {
            $eventClass = ucfirst($input);
            $eventName = $input;

            // Try a direct class
            if (class_exists($eventClass, true))
            {
                $event = new $eventClass();
            }

            // Try a core event
            elseif (class_exists("\FuzeWorks\Event\\".$eventClass, true))
            {
                $class = "\FuzeWorks\Event\\".$eventClass;
                $event = new $class();
            }

            // Try a notifier event
            elseif (func_num_args() == 1)
            {
                $event = new NotifierEvent();
            }

            // Or throw an exception on failure
            else
            {
                throw new EventException('Event '.$eventName.' could not be found!', 1);
            }
        }
        else
        {
            throw new EventException('Event could not be loaded. Invalid variable provided.', 1);
        }

        if (func_num_args() > 1) {
            call_user_func_array(array($event, 'init'), array_slice(func_get_args(), 1));
        }

        // Do not run if the event system is disabled
        if (!self::$enabled) {
            return $event;
        }

        //There are listeners for this event
        if (isset(self::$listeners[$eventName])) {
            // Event with listeners found. Log it.
            Logger::newLevel("Firing Event: '".$eventName."'. Found listeners: ");

            //Loop from the highest priority to the lowest
            for ($priority = Priority::getHighestPriority(); $priority <= Priority::getLowestPriority(); ++$priority) {
                //Check for listeners in this priority
                if (isset(self::$listeners[$eventName][$priority])) {
                    $listeners = self::$listeners[$eventName][$priority];
                    Logger::newLevel('Listeners with priority '.Priority::getPriority($priority));
                    //Fire the event to each listener
                    foreach ($listeners as $callbackArray) {
                        // @codeCoverageIgnoreStart
                        $callback = $callbackArray[0];
                        if (is_array($callback)) {
                            Logger::newLevel('Firing '.get_class($callback[0]).'->'.$callback[1]);
                        }  elseif (is_callable($callback)) {
                            Logger::newLevel('Firing function');
                        }  else {
                            Logger::newLevel('Firing '.implode('->', $callback));
                        }
                        // @codeCoverageIgnoreEnd

                        // Merge arguments and call listener
                        $args = array_merge(array($event), $callbackArray[1]);
                        call_user_func_array($callback, $args);
                        Logger::stopLevel();
                    }

                    Logger::stopLevel();
                }
            }

            Logger::stopLevel();
        }

        return $event;
    }

    /**
     * Enables the event system.
     */
    public static function enable()
    {
        Logger::log('Enabled the Event system');
        self::$enabled = true;
    }

    /**
     * Disables the event system.
     */
    public static function disable()
    {
        Logger::log('Disabled the Event system');
        self::$enabled = false;
    }
}
