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

use FuzeWorks\Core\Exception\ConfigException;
use FuzeWorks\Core\Exception\ConfiguratorException;
use FuzeWorks\Core\Exception\CoreException;
use FuzeWorks\Core\Exception\EventException;
use FuzeWorks\Core\Exception\Exception;
use FuzeWorks\Core\Exception\FactoryException;
use FuzeWorks\Core\Exception\HelperException;
use FuzeWorks\Core\Exception\InvalidArgumentException;
use FuzeWorks\Core\Exception\LibraryException;
use FuzeWorks\Core\Exception\LoggerException;

/**
 * Class ExceptionTest.
 *
 * Exception testing suite, tests if all exceptions can be fired
 */
class ExceptionTest extends CoreTestAbstract
{

    public function testException()
    {
        $this->expectException(Exception::class);
        throw new Exception("Exception Test Run", 1);
    }

    public function testCoreException()
    {
        $this->expectException(CoreException::class);
        throw new CoreException("Exception Test Run", 1);
    }

    public function testConfigException()
    {
        $this->expectException(ConfigException::class);
        throw new ConfigException("Exception Test Run", 1);
    }

    public function testEventException()
    {
        $this->expectException(EventException::class);
        throw new EventException("Exception Test Run", 1);
    }

    public function testFactoryException()
    {
        $this->expectException(FactoryException::class);
        throw new FactoryException("Exception Test Run", 1);
    }

    public function testHelperException()
    {
        $this->expectException(HelperException::class);
        throw new HelperException("Exception Test Run", 1);
    }

    public function testInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        throw new InvalidArgumentException("Exception Test Run", 1);
    }

    public function testLibraryException()
    {
        $this->expectException(LibraryException::class);
        throw new LibraryException("Exception Test Run", 1);
    }

    public function testLoggerException()
    {
        $this->expectException(LoggerException::class);
        throw new LoggerException("Exception Test Run", 1);
    }

    public function testConfiguratorException()
    {
        $this->expectException(ConfiguratorException::class);
        throw new ConfiguratorException("Exception Test Run", 1);
    }

}
