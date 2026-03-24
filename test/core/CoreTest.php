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

use FuzeWorks\Core\Core;

/**
 * Class CoreTest.
 *
 * Core testing suite, will test basic core functionality
 * @coversDefaultClass \FuzeWorks\Core\Core
 */
class CoreTest extends CoreTestAbstract
{
    public function testCanLoadStartupFiles()
    {
        // Assert
        $this->assertTrue(class_exists('FuzeWorks\Core\Core'));
        $this->assertTrue(class_exists('FuzeWorks\Core\Config'));
        $this->assertTrue(class_exists('FuzeWorks\Core\Configurator'));
        $this->assertTrue(trait_exists('FuzeWorks\Core\ComponentPathsTrait'));
        $this->assertTrue(class_exists('FuzeWorks\Core\DeferredComponentClass'));
        $this->assertTrue(class_exists('FuzeWorks\Core\Event'));
        $this->assertTrue(class_exists('FuzeWorks\Core\Events'));
        $this->assertTrue(class_exists('FuzeWorks\Core\Factory'));
        $this->assertTrue(class_exists('FuzeWorks\Core\Helpers'));
        $this->assertTrue(interface_exists('FuzeWorks\Core\iComponent'));
        $this->assertTrue(interface_exists('FuzeWorks\Core\iPluginHeader'));
        $this->assertTrue(class_exists('FuzeWorks\Core\Libraries'));
        $this->assertTrue(class_exists('FuzeWorks\Core\Logger'));
        $this->assertTrue(class_exists('FuzeWorks\Core\Plugins'));
        $this->assertTrue(class_exists('FuzeWorks\Core\Priority'));
    }

    /**
     * @covers ::isPHP
     */
    public function testIsPHP()
    {
        $this->assertTrue(Core::isPHP('1.2.0'));
        $this->assertFalse(Core::isphp('9999.9.9'));
    }

    /**
     * @covers ::getEnv
     */
    public function testGetEnv()
    {
        // First push some test variables
        putenv('TESTGETENV=AFFIRMED');

        // Then try and fetch using the method
        $this->assertEquals('AFFIRMED', Core::getEnv('TESTGETENV'));

        // Also test variables that don't exist
        $this->assertNull(Core::getEnv('TESTNOTEXIST'));
        $this->assertEquals('replacement', Core::getEnv('TESTNOTEXISTTWO', 'replacement'));
    }
}
