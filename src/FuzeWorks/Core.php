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

use Exception;
use FuzeWorks\Exception\CoreException;
use FuzeWorks\Exception\EventException;

/**
 * FuzeWorks Core.
 *
 * Holds all the modules and starts the framework. Allows for starting and managing modules
 *
 * @todo Implement unit tests for autoloader() methods
 * @author    TechFuze <contact@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Core
{
    /**
     * The current version of the framework.
     *
     * @var string Framework version
     */
    public static string $version = '1.2.0';

    /**
     * Working directory of the Framework.
     *
     * This is required to make the shutdown function working under Apache webservers
     *
     * @var string
     */
    public static string $cwd;

    public static string $coreDir;

    public static string $tempDir;

    public static string $logDir;

    /**
     * Array of exception handlers, sorted by priority
     *
     * @var array
     */
    protected static array $exceptionHandlers = [];

    /**
     * Array of error handlers, sorted by priority
     *
     * @var array
     */
    protected static array $errorHandlers = [];

    /**
     * Array of all classMaps which can be autoloaded.
     *
     * @var array
     */
    protected static array $autoloadMap = [];

    /**
     * Initializes the core.
     *
     * @throws Exception
     */
    public static function init(): Factory
    {
        // Set the CWD for usage in the shutdown function
        self::$cwd = getcwd();

        // Set the core dir for when the loading of classes is required
        self::$coreDir = dirname(__DIR__);
        
        // Defines the time the framework starts. Used for timing functions in the framework
        if (!defined('STARTTIME')) {
            define('STARTTIME', microtime(true));
            define('DS', DIRECTORY_SEPARATOR);
        }

        // Load basics
        ignore_user_abort(true);
        register_shutdown_function(array('\FuzeWorks\Core', 'shutdown'));
        set_error_handler(array('\FuzeWorks\Core', 'errorHandler'), E_ALL);
        set_exception_handler(array('\FuzeWorks\Core', 'exceptionHandler'));
        spl_autoload_register(['\FuzeWorks\Core', 'autoloader']);

        // Return the Factory
        return new Factory();
    }

    /**
     * Stop FuzeWorks and run all shutdown functions.
     *
     * Afterwards run the Logger shutdown function in order to possibly display the log
     * @throws EventException
     */
    public static function shutdown(): void
    {
        // Fix Apache bug where CWD is changed upon shutdown
        chdir(self::$cwd);

        // Log the shutdown
        Logger::newLevel("Shutting FuzeWorks down gracefully");

        // Fire the Shutdown event
        $event = Events::fireEvent('coreShutdownEvent');
        if ($event->isCancelled() === false)
        {
            Logger::shutdownError();
            Logger::shutdown();
        }

        Logger::stopLevel();
    }

    /**
     * Retrieve a variable name from the php environment, while also providing a fallback variable
     *
     * @param string $varName
     * @param string|null $default
     * @return string|null
     */
    public static function getEnv(string $varName, string $default = null): string|null
    {
        // First retrieve the environment variable
        $var = getenv($varName);

        // If the environment variable doesn't exist, use the default one
        if ($var === FALSE)
            return $default;

        // Otherwise, return the variable itself
        return $var;
    }

    /**
     * Checks whether the current running version of PHP is equal to the input string.
     *
     * @param  string $version
     * @return  bool    true if running higher than input string
     */
    public static function isPHP(string $version): bool
    {
        static $_is_php;
        $version = (string) $version;

        if ( ! isset($_is_php[$version]))
        {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }

    public static function exceptionHandler(): void
    {
        for ($i = Priority::getHighestPriority(); $i <= Priority::getLowestPriority(); $i++)
        {
            if (!isset(self::$exceptionHandlers[$i]))
                continue;

            foreach (self::$exceptionHandlers[$i] as $handler)
                call_user_func_array($handler, func_get_args());
        }
    }

    public static function errorHandler(): void
    {
        for ($i = Priority::getHighestPriority(); $i <= Priority::getLowestPriority(); $i++)
        {
            if (!isset(self::$errorHandlers[$i]))
                continue;

            foreach (self::$errorHandlers[$i] as $handler)
                call_user_func_array($handler, func_get_args());
        }
    }

    /**
     * Add an exception handler to be called when an exception occurs
     *
     * @param callable $callback
     * @param int $priority
     */
    public static function addExceptionHandler(callable $callback, int $priority = Priority::NORMAL): void
    {
        if (!isset(self::$exceptionHandlers[$priority]))
            self::$exceptionHandlers[$priority] = [];

        if (!in_array($callback, self::$exceptionHandlers[$priority]))
            self::$exceptionHandlers[$priority][] = $callback;
    }

    /**
     * Remove an exception handler from the list
     *
     * @param callable $callback
     * @param int $priority
     */
    public static function removeExceptionHandler(callable $callback, int $priority = Priority::NORMAL): void
    {
        if (isset(self::$exceptionHandlers[$priority]) && in_array($callback, self::$exceptionHandlers[$priority]))
        {
            foreach (self::$exceptionHandlers[$priority] as $i => $_callback)
                if ($callback == $_callback)
                    unset(self::$exceptionHandlers[$priority][$i]);
        }
    }

    /**
     * Add an error handler to be called when an error occurs
     *
     * @param callable $callback
     * @param int $priority
     */
    public static function addErrorHandler(callable $callback, int $priority = Priority::NORMAL): void
    {
        if (!isset(self::$errorHandlers[$priority]))
            self::$errorHandlers[$priority] = [];

        if (!in_array($callback, self::$errorHandlers[$priority]))
            self::$errorHandlers[$priority][] = $callback;
    }

    /**
     * Remove an error handler from the list
     *
     * @param callable $callback
     * @param int $priority
     */
    public static function removeErrorHandler(callable $callback, int $priority = Priority::NORMAL): void
    {
        if (isset(self::$errorHandlers[$priority]) && in_array($callback, self::$errorHandlers[$priority]))
        {
            foreach (self::$errorHandlers[$priority] as $i => $_callback)
                if ($callback == $_callback)
                    unset(self::$errorHandlers[$priority][$i]);
        }
    }

    /**
     * @param string $nameSpacePrefix
     * @param string $filePath
     * @throws CoreException
     */
    public static function addAutoloadMap(string $nameSpacePrefix, string $filePath): void
    {
        // Remove leading slashes
        $nameSpacePrefix = ltrim($nameSpacePrefix, '\\');

        if (isset(self::$autoloadMap[$nameSpacePrefix]))
            throw new CoreException("Could not add classes to autoloader. ClassMap already exists.");

        if (!file_exists($filePath) && !is_dir($filePath))
            throw new CoreException("Could not add classes to autoloader. Provided filePath does not exist.");

        self::$autoloadMap[$nameSpacePrefix] = $filePath;
    }

    public static function autoloader(string $class): void
    {
        // Remove leading slashes
        $class = ltrim($class, '\\');

        // First attempt and find if the prefix of the class is in the autoloadMap
        foreach (self::$autoloadMap as $prefix => $path)
        {
            // If not, try next
            if (!str_contains($class, $prefix))
                continue;

            // If it contains the prefix, attempt to find the file
            $className = substr($class, strlen($prefix) + 1);
            $filePath = $path . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
            if (file_exists($filePath) && is_file($filePath))
                require($filePath);
        }
    }

    /**
     * Clears the autoloader to its original state.
     *
     * Not intended for use by developer. Only for use during testing
     * @internal
     */
    public static function clearAutoloader(): void
    {
        self::$autoloadMap = [];
    }

    /**
     * Tests for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute. is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @link    https://bugs.php.net/bug.php?id=54709
     * @param   string $file
     * @return  bool
     */
    public static function isReallyWritable(string $file): bool
    {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR === '/' && ! ini_get('safe_mode'))
            return is_writable($file);

        /* For Windows servers and safe_mode "on" installations we'll actually
         * write a file then read it. Bah...
         */
        if (is_dir($file))
        {
            $file = rtrim($file, '/').'/'.md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === FALSE)
            {
                return FALSE;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return TRUE;
        }
        elseif ( ! is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE)
            return FALSE;

        fclose($fp);
        return TRUE;
    }

    /**
     * Whether the current environment is a production environment
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return (ENVIRONMENT === 'PRODUCTION');
    }
}
