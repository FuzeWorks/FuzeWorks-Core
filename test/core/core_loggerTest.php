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

use FuzeWorks\Events;
use FuzeWorks\Logger;
use FuzeWorks\Factory;
use FuzeWorks\Exception\LoggerException;

/**
 * Class ModelTest.
 *
 * Will test the FuzeWorks Model System.
 * @coversDefaultClass \FuzeWorks\Logger
 */
class loggerTest extends CoreTestAbstract
{
    protected Logger $logger;

    protected string $output;

    public function setUp(): void
    {
        Factory::getInstance()->config->get('error')->fuzeworks_error_reporting = false;
        Logger::$logs = array();
    }

    /**
     * @coversNothing
     */
    public function testGetLogger()
    {
        $this->assertInstanceOf('FuzeWorks\Logger', new Logger);
        Factory::getInstance()->config->get('error')->php_error_reporting = true;
        $this->assertInstanceOf('FuzeWorks\Logger', new Logger);
    }

    /**
     * @covers ::errorHandler
     */
    public function testErrorHandler()
    {
        Logger::errorHandler(E_ERROR, 'Example error', __FILE__, 1);
        $this->assertCount(1, Logger::$logs);

        $log = Logger::$logs[0];
        $this->assertEquals('ERROR', $log['type']);
        $this->assertEquals('Example error', $log['message']);
        $this->assertEquals(__FILE__, $log['logFile']);
        $this->assertEquals(1, $log['logLine']);
    }

    /**
     * @depends testErrorHandler
     * @covers ::errorHandler
     * @covers ::getType
     */
    public function testErrorHandlerTypes()
    {
        $types = array(
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'ERROR',
            E_NOTICE => 'WARNING',
            E_CORE_ERROR => 'ERROR',
            E_CORE_WARNING => 'WARNING',
            E_COMPILE_ERROR => 'ERROR',
            E_COMPILE_WARNING => 'WARNING',
            E_USER_ERROR => 'ERROR',
            E_USER_WARNING => 'WARNING',
            E_USER_NOTICE => 'WARNING',
            E_USER_DEPRECATED => 'WARNING',
            E_STRICT => 'ERROR',
            E_RECOVERABLE_ERROR => 'ERROR',
            E_DEPRECATED => 'WARNING',
            0 => 'Unknown error: 0'
        );

        foreach ($types as $errorType => $output) {
            // Clear the log entries
            Logger::$logs = array();

            // Log the error
            Logger::errorHandler($errorType, 'Log message');

            // Fetch the error
            $log = Logger::$logs[0];

            // Check the type
            $this->assertEquals($output, $log['type']);
        }
    }

    /**
     * @covers ::exceptionHandler
     */
    public function testExceptionHandler()
    {
        // Create the exception
        $exception = new LoggerException("FAILURE");

        // Prepare to intercept
        Events::addListener(function($event){
            $event->setCancelled(true);
            $this->assertEquals('FAILURE', $event->log['message']);
            $this->assertEquals("EXCEPTION", $event->log['type']);
        }, 'haltExecutionEvent');

        // Log the exception
        Logger::exceptionHandler($exception);
    }

    /**
     * @covers ::exceptionHandler
     * @depends testExceptionHandler
     */
    public function testErrorsToExceptionHandler()
    {
        // Create the error
        $error = new ParseError("FAILURE_ERROR");

        // Prepare to intercept
        Events::addListener(function ($event) {
            $event->setCancelled(true);
            $this->assertEquals("FAILURE_ERROR", $event->log['message']);
            $this->assertEquals("ERROR", $event->log['type']);
        }, 'haltExecutionEvent');

        Logger::exceptionHandler($error);
    }

    /**
     * @covers ::log
     */
    public function testLog()
    {
        // Log the message
        Logger::log('Log message', 'core_loggerTest', __FILE__, 1);

        // Fetch the message
        $log = Logger::$logs[0];

        // Assert data
        $this->assertEquals('INFO', $log['type']);
        $this->assertEquals('Log message', $log['message']);
        $this->assertEquals(__FILE__, $log['logFile']);
        $this->assertEquals(1, $log['logLine']);
        $this->assertEquals('core_loggerTest', $log['context']);
    }

    /**
     * @depends testLog
     * @covers ::newLevel
     * @covers ::stopLevel
     * @covers ::logError
     * @covers ::logWarning
     * @covers ::logDebug
     * @covers ::mark
     */
    public function testLogTypes()
    {
        $types = array(
                'newLevel' => 'LEVEL_START',
                'stopLevel' => 'LEVEL_STOP',
                'logError' => 'ERROR',
                'logWarning' => 'WARNING',
                'logDebug' => 'DEBUG',
                'mark' => 'BMARK'
            );

        foreach ($types as $method => $returnValue) {
            // Clear the log entries
            Logger::$logs = array();

            // Log the entry
            Logger::{$method}('Log message', 'core_loggerTest', __FILE__, 1);

            // Fetch the entry
            $log = Logger::$logs[0];

            // Assert the entry
            $this->assertEquals($returnValue, $log['type']);
        }
    }

    /**
     * @covers ::enable
     * @covers ::disable
     * @covers ::isEnabled
     */
    public function testEnableDisable()
    {
        // First enable
        Logger::enable();
        $this->assertTrue(Logger::isEnabled());

        // Then disable
        Logger::disable();
        $this->assertFalse(Logger::isEnabled());
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Logger::disable();
        Logger::$logs = array();
    }
}
