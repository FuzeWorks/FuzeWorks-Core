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

use FuzeWorks\Exception\ConfigException;
use FuzeWorks\Exception\EventException;
use FuzeWorks\Exception\Exception;

/**
 * Logger Class.
 *
 * The main tool to handle errors and exceptions. Provides some tools for debugging and tracking where errors take place
 * All fatal errors get catched by this class and get displayed if configured to do so.
 * Also provides utilities to benchmark the application.
 *
 * @author    TechFuze <contact@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Logger {

    /**
     * All log entries, unsorted.
     *
     * @var array
     */
    public static $logs = [];

    /**
     * whether to output the log after FuzeWorks has run.
     *
     * @var bool
     */
    private static $print_to_screen = false;

    /**
     * Whether the Logger has been enabled or not
     *
     * @var bool
     */
    private static $isEnabled = false;

    /**
     * whether to output the log of the last entire request to a file after FuzeWorks has run.
     *
     * @var bool
     */
    private static $log_last_request = false;

    /**
     * Whether to output the log of all errors to a file after FuzeWorks has run
     *
     * @var bool
     */
    private static $log_errors_to_file = false;

    /**
     * The template to use when parsing the debug log
     * 
     * @var string Template name
     */
    private static $logger_template = 'logger_cli';

    /**
     * whether to output the log after FuzeWorks has run, regardless of conditions.
     *
     * @var bool
     */
    public static $debug = false;

    /**
     * List of all benchmark markpoints.
     * 
     * @var array
     */
    public static $markPoints = [];

    /**
     * Initiates the Logger.
     *
     * Registers the error and exception handler, when required to do so by configuration
     * @throws ConfigException
     */
    public function __construct()
    {
        // Get the config file
        $cfg_error = Factory::getInstance()->config->getConfig('error');

        // Register the error handler, Untestable
        // @codeCoverageIgnoreStart
        if ($cfg_error->get('fuzeworks_error_reporting') == true)
        {
            self::enableHandlers();
        }
        // @codeCoverageIgnoreEnd

        // Set PHP error reporting
        if ($cfg_error->get('php_error_reporting'))
            error_reporting(true);
        else
            error_reporting(false);

        // Set the environment variables
        self::$log_last_request = $cfg_error->get('log_last_request_to_file');
        self::$log_errors_to_file = $cfg_error->get('log_errors_to_file');
        self::newLevel('Logger Initiated');
    }

    /**
     * Enable error to screen logging.
     */
    public static function enable()
    {
        self::$isEnabled = true;
    }

    /**
     * Disable error to screen logging.
     */
    public static function disable()
    {
        self::$isEnabled = false;
    }

    /**
     * Returns whether screen logging is enabled.
     */
    public static function isEnabled(): bool
    {
        return self::$isEnabled;
    }

    /**
     * Enable outputting the debugger after the request has been processed
     */
    public static function enableScreenLog()
    {
        if (!Core::isProduction())
            self::$print_to_screen = true;
    }

    /**
     * Disable outputting the debugger after the request has been processed
     */
    public static function disableScreenLog()
    {
        self::$print_to_screen = false;
    }

    /**
     * Enable FuzeWorks error handling
     *
     * Registers errorHandler() and exceptionHandler() as the respective handlers for PHP
     * @codeCoverageIgnore
     */
    public static function enableHandlers()
    {
        Core::addErrorHandler(['\FuzeWorks\Logger', 'errorHandler'], Priority::NORMAL);
        Core::addExceptionHandler(['\FuzeWorks\Logger', 'exceptionHandler'], Priority::NORMAL);
    }

    /**
     * Disable FuzeWorks error handling
     *
     * Unregisters errorHandler() and exceptionHandler() as the respective handlers for PHP
     * @codeCoverageIgnore
     */
    public static function disableHandlers()
    {
        Core::removeErrorHandler(['\FuzeWorks\Logger', 'errorHandler'], Priority::NORMAL);
        Core::removeExceptionHandler(['\FuzeWorks\Logger', 'exceptionHandler'], Priority::NORMAL);
    }

    /**
     * Function to be run upon FuzeWorks shutdown.
     *
     * @codeCoverageIgnore
     *
     * Logs data to screen when requested to do so
     * @throws EventException
     */
    public static function shutdown()
    {
        // And finally stop the Logging
        self::stopLevel();

        if (self::$debug === true || self::$print_to_screen) {
            self::log('Parsing debug log');
            self::logToScreen();
        }

        if (self::$log_last_request == true)
            self::logLastRequest();

        if (self::$log_errors_to_file == true)
            self::logErrorsToFile();
    }

    /**
     * Function to be run upon FuzeWorks shutdown.
     *
     * @codeCoverageIgnore
     *
     * Logs a fatal error and outputs the log when configured or requested to do so
     */
    public static function shutdownError()
    {
        $error = error_get_last();
        if ($error !== null) {
             // Log it!
            $thisType = self::getType($error['type']);
            $LOG = array('type' => (!is_null($thisType) ? $thisType : 'ERROR'),
                'message' => $error['message'],
                'logFile' => $error['file'],
                'logLine' => $error['line'],
                'runtime' => round(self::getRelativeTime(), 4),);
            self::$logs[] = $LOG;

            if ($thisType == 'ERROR')
            {
               self::haltExecution($LOG);
            }
        }
    }

    /**
     * System that redirects the errors to the appropriate logging method.
     *
     * @param int $type Error-type, Pre defined PHP Constant
     * @param string error. The error itself
     * @param string File. The absolute path of the file
     * @param int Line. The line on which the error occured.
     * @param array context. Some of the error's relevant variables
     */
    public static function errorHandler($type = E_USER_NOTICE, $error = 'Undefined Error', $errFile = null, $errLine = null)
    {
        // Check type
        $thisType = self::getType($type);
        $LOG = array('type' => (!is_null($thisType) ? $thisType : 'ERROR'),
            'message' => (!is_null($error) ? $error : ''),
            'logFile' => (!is_null($errFile) ? $errFile : ''),
            'logLine' => (!is_null($errLine) ? $errLine : ''),
            'runtime' => round(self::getRelativeTime(), 4),);
        self::$logs[] = $LOG;
    }

    /**
     * Exception handler
     * Will be triggered when an uncaught exception occures. This function shows the error-message, and shuts down the script.
     * Please note that most of the user-defined exceptions will be caught in the router, and handled with the error-controller.
     *
     * @param Exception $exception The occured exception.
     * @param bool $haltExecution. Defaults to true
     */
    public static function exceptionHandler($exception, bool $haltExecution = true)
    {
        $LOG = array('type' => 'EXCEPTION',
            'message' => $exception->getMessage(),
            'logFile' => $exception->getFile(),
            'logLine' => $exception->getLine(),
            'context' => $exception->getTraceAsString(),
            'runtime' => round(self::getRelativeTime(), 4),);
        self::$logs[] = $LOG;

        // And return a 500 because this error was fatal
        if ($haltExecution)
            self::haltExecution($LOG);
    }

    /**
     * Set the template that FuzeWorks should use to parse debug logs
     * 
     * @codeCoverageIgnore
     * 
     * @var string Name of the template file
     */
    public static function setLoggerTemplate($templateName)
    {
        self::$logger_template = $templateName;
    }

    /**
     * Output the entire log to the screen. Used for debugging problems with your code.
     * @codeCoverageIgnore
     * @throws EventException
     */
    public static function logToScreen()
    {
        // Send a screenLogEvent, allows for new screen log designs
        $event = Events::fireEvent('screenLogEvent');
        if ($event->isCancelled()) {
            return;
        }

        $logs = self::$logs;
        require(dirname(__DIR__) . DS . 'Layout' . DS . 'layout.' . self::$logger_template . '.php');
    }

    /**
     * Output the entire log to a file. Used for debugging problems with your code.
     * @codeCoverageIgnore
     */
    public static function logLastRequest()
    {
        ob_start(function () {});
        $logs = self::$logs;
        require(dirname(__DIR__) . DS . 'Layout' . DS . 'layout.logger_file.php');
        $contents = ob_get_clean();
        $file = Core::$logDir . DS . 'fwlog_request.log';
        if (is_writable(dirname($file)))
            file_put_contents($file, $contents);
    }

    /**
     * Output all errors to a file. Used for tracking all errors in FuzeWorks and associated code
     * @codeCoverageIgnore
     */
    public static function logErrorsToFile()
    {
        ob_start(function() {});
        $logs = [];
        foreach (self::$logs as $log)
        {
            if ($log['type'] === 'ERROR' || $log['type'] === 'EXCEPTION')
                $logs[] = $log;
        }
        require(dirname(__DIR__) . DS . 'Layout' . DS . 'layout.logger_file.php');
        $contents = ob_get_clean();
        $file = Core::$logDir . DS . 'fwlog_errors.log';
        if (is_writable(dirname($file)))
            file_put_contents($file, $contents, FILE_APPEND);
    }

    /* =========================================LOGGING METHODS============================================================== */

    /**
     * Set a benchmark mark point.
     * 
     * Multiple calls to this function can be made so that several
     * execution points can be timed.
     * 
     * @param   string    $name   Marker name
     * @return  void
     */
    public static function mark($name)
    {
        $LOG = array('type' => 'BMARK',
            'message' => (!is_null($name) ? $name : ''),
            'logFile' => '',
            'logLine' => '',
            'context' => '',
            'runtime' => round(self::getRelativeTime(), 4),);

        self::$logs[] = $LOG;
    }

    /**
     * Create a information log entry.
     *
     * @param string $msg  The information to be logged
     * @param string $mod  The name of the module
     * @param string $file The file where the log occurred
     * @param int    $line The line where the log occurred
     */
    public static function log($msg, $mod = null, $file = null, $line = null)
    {
        self::logInfo($msg, $mod, $file, $line);
    }

    /**
     * Create a information log entry.
     *
     * @param string $msg  The information to be logged
     * @param string $mod  The name of the module
     * @param string $file The file where the log occurred
     * @param int    $line The line where the log occurred
     */
    public static function logInfo($msg, $mod = null, $file = null, $line = null)
    {
        $LOG = array('type' => 'INFO',
            'message' => (!is_null($msg) ? $msg : ''),
            'logFile' => (!is_null($file) ? $file : ''),
            'logLine' => (!is_null($line) ? $line : ''),
            'context' => (!is_null($mod) ? $mod : ''),
            'runtime' => round(self::getRelativeTime(), 4),);

        self::$logs[] = $LOG;
    }

    /**
     * Create a information log entry.
     *
     * @param string $msg  The information to be logged
     * @param string $mod  The name of the module
     * @param string $file The file where the log occurred
     * @param int    $line The line where the log occurred
     */
    public static function logDebug($msg, $mod = null, $file = null, $line = null)
    {
        $LOG = array('type' => 'DEBUG',
            'message' => (!is_null($msg) ? $msg : ''),
            'logFile' => (!is_null($file) ? $file : ''),
            'logLine' => (!is_null($line) ? $line : ''),
            'context' => (!is_null($mod) ? $mod : ''),
            'runtime' => round(self::getRelativeTime(), 4),);

        self::$logs[] = $LOG;
    }

    /**
     * Create a error log entry.
     *
     * @param string $msg  The information to be logged
     * @param string $mod  The name of the module
     * @param string $file The file where the log occurred
     * @param int    $line The line where the log occurred
     */
    public static function logError($msg, $mod = null, $file = null, $line = null)
    {
        $LOG = array('type' => 'ERROR',
            'message' => (!is_null($msg) ? $msg : ''),
            'logFile' => (!is_null($file) ? $file : ''),
            'logLine' => (!is_null($line) ? $line : ''),
            'context' => (!is_null($mod) ? $mod : ''),
            'runtime' => round(self::getRelativeTime(), 4),);

        self::$logs[] = $LOG;
    }

    /**
     * Create a warning log entry.
     *
     * @param string $msg  The information to be logged
     * @param string $mod  The name of the module
     * @param string $file The file where the log occurred
     * @param int    $line The line where the log occurred
     */
    public static function logWarning($msg, $mod = null, $file = null, $line = null)
    {
        $LOG = array('type' => 'WARNING',
            'message' => (!is_null($msg) ? $msg : ''),
            'logFile' => (!is_null($file) ? $file : ''),
            'logLine' => (!is_null($line) ? $line : ''),
            'context' => (!is_null($mod) ? $mod : ''),
            'runtime' => round(self::getRelativeTime(), 4),);

        self::$logs[] = $LOG;
    }

    /**
     * Create a new Level log entry. Used to categorise logs.
     *
     * @param string $msg  The name of the new level
     * @param string $mod  The name of the module
     * @param string $file The file where the log occurred
     * @param int    $line The line where the log occurred
     */
    public static function newLevel($msg, $mod = null, $file = null, $line = null)
    {
        $LOG = array('type' => 'LEVEL_START',
            'message' => (!is_null($msg) ? $msg : ''),
            'logFile' => (!is_null($file) ? $file : ''),
            'logLine' => (!is_null($line) ? $line : ''),
            'context' => (!is_null($mod) ? $mod : ''),
            'runtime' => round(self::getRelativeTime(), 4),);

        self::$logs[] = $LOG;
    }

    /**
     * Create a stop Level log entry. Used to close log categories.
     *
     * @param string $msg  The name of the new level
     * @param string $mod  The name of the module
     * @param string $file The file where the log occurred
     * @param int    $line The line where the log occurred
     */
    public static function stopLevel($msg = null, $mod = null, $file = null, $line = null)
    {
        $LOG = array('type' => 'LEVEL_STOP',
            'message' => (!is_null($msg) ? $msg : ''),
            'logFile' => (!is_null($file) ? $file : ''),
            'logLine' => (!is_null($line) ? $line : ''),
            'context' => (!is_null($mod) ? $mod : ''),
            'runtime' => round(self::getRelativeTime(), 4),);

        self::$logs[] = $LOG;
    }

    /* =========================================OTHER METHODS============================================================== */

    /**
     * Returns a string representation of an error
     * Turns a PHP error-constant (or integer) into a string representation.
     *
     * @param int $type PHP-constant errorType (e.g. E_NOTICE).
     *
     * @return string String representation
     */
    public static function getType($type): string
    {
        switch ($type) {
            case E_ERROR:
                return 'ERROR';
            case E_WARNING:
                return 'WARNING';
            case E_PARSE:
                return 'ERROR';
            case E_NOTICE:
                return 'WARNING';
            case E_CORE_ERROR:
                return 'ERROR';
            case E_CORE_WARNING:
                return 'WARNING';
            case E_COMPILE_ERROR:
                return 'ERROR';
            case E_COMPILE_WARNING:
                return 'WARNING';
            case E_USER_ERROR:
                return 'ERROR';
            case E_USER_WARNING:
                return 'WARNING';
            case E_USER_NOTICE:
                return 'WARNING';
            case E_USER_DEPRECATED:
                return 'WARNING';
            case E_STRICT:
                return 'ERROR';
            case E_RECOVERABLE_ERROR:
                return 'ERROR';
            case E_DEPRECATED:
                return 'WARNING';
        }

        return $type = 'Unknown error: ' . $type;
    }

    /**
     * Halts the Execution of FuzeWorks
     *
     * Will die a message if not intercepted by haltExecutionEvent.
     * @param array $log
     * @codeCoverageIgnore
     */
    public static function haltExecution(array $log)
    {
        self::logError("Halting execution...");
        try {
            $event = Events::fireEvent("haltExecutionEvent", $log);
        } catch (EventException $e) {
            self::logError("Can't fire haltExecutionEvent: '".$e->getMessage()."'");
            die(PHP_EOL . "FuzeWorks execution halted. See error log for more information");
        }
        if ($event->isCancelled() == true)
            return;

        die(PHP_EOL . "FuzeWorks execution halted. See error log for more information");
    }

    /**
     * Get the relative time since the framework started.
     *
     * Used for debugging timings in FuzeWorks
     *
     * @return float Time passed since FuzeWorks init
     */
    private static function getRelativeTime(): float
    {
        $startTime = STARTTIME;
        $time = microtime(true) - $startTime;

        return $time;
    }
}