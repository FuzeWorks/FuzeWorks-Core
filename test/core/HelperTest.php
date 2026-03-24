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

use FuzeWorks\Core\Events;
use FuzeWorks\Core\Helpers;
use FuzeWorks\Core\Priority;

/**
 * Class HelperTest.
 *
 * Helpers testing suite, will test basic loading of Helpers
 * @coversDefaultClass \FuzeWorks\Core\Helpers
 */
class HelperTest extends CoreTestAbstract
{

    /**
     * @var Helpers
     */
	protected Helpers $helpers;

	public function setUp(): void
	{
		// Prepare class
	    $this->helpers = new Helpers();
		$this->helpers->setDirectories([3 => ['test' . DS . 'helpers']]);
	}

    /**
     * @coversNothing
     */
    public function testGetHelpersClass()
    {
        $this->assertInstanceOf('FuzeWorks\Core\Helpers', $this->helpers);
    }

    /**
     * @covers ::load
     */
    public function testLoadHelper()
    {
    	// First test if the function/helper is not loaded yet
    	$this->assertFalse(function_exists('testHelperFunction'));

    	// Test if the helper is properly loaded
    	$this->assertTrue($this->helpers->load('TestLoadHelper'));

    	// Test if the function exists now
    	$this->assertTrue(function_exists('testHelperFunction'));
    }

    /**
     * @depends testLoadHelper
     * @covers ::load
     */
    public function testLoadHelperWithoutSubdirectory()
    {
        // First test if the function/helper is not loaded yet
        $this->assertFalse(function_exists('testLoadHelperWithoutSubdirectory'));

        // Try and load the helper
        $this->assertTrue($this->helpers->load('TestLoadHelperWithoutSubdirectory'));

        // Then test if the function/helper is loaded
        $this->assertTrue(function_exists('testLoadHelperWithoutSubdirectory'));
    }

    /**
     * @depends testLoadHelper
     * @covers ::load
     */
    public function testLoadHelperWithAltDirectory()
    {
        $this->assertFalse(function_exists('testLoadHelperWithAltDirectory'));

        $this->assertTrue($this->helpers->load(
            'TestLoadHelperWithAltDirectory',
            ['test' . DS . 'helpers' . DS . 'TestLoadHelperWithAltDirectory' . DS . 'SubDirectory']
        ));

        $this->assertTrue(function_exists('testLoadHelperWithAltDirectory'));
    }

    /**
     * @depends testLoadHelper
     * @covers ::load
     */
    public function testReloadHelper()
    {
        // First test if the function/helper is not loaded yet
        $this->assertFalse(function_exists('testReloadHelper'));

        // Try and load the helper
        $this->assertTrue($this->helpers->load('TestReloadHelper'));

        // Then test if the function/helper is loaded
        $this->assertTrue(function_exists('testReloadHelper'));

        // Try and reload the helper
        $this->assertFalse($this->helpers->load('TestReloadHelper'));

        // Test that the function still exists
        $this->assertTrue(function_exists('testReloadHelper'));
    }

    /**
     * @depends testLoadHelper
     * @covers ::load
     */
    public function testCancelLoadHelper()
    {
        // First test if the function/helper is not loaded yet
        $this->assertFalse(function_exists('testCancelLoadHelper'));

        // Prepare listener
        Events::addListener(function($event) {
            $event->setCancelled(true);

        }, 'HelperLoadEvent', Priority::NORMAL);

        $this->assertFalse($this->helpers->load('TestCancelLoadHelper'));
    }

    /**
     * @depends testLoadHelper
     * @covers ::get
     */
    public function testGetHelper()
    {
        // First test if the function/helper is not loaded yet
        $this->assertFalse(function_exists('testGetHelper'));

        // Test if the helper is properly loaded
        $this->assertTrue($this->helpers->get('TestGetHelper'));

        // Test if the function exists now
        $this->assertTrue(function_exists('testGetHelper'));
    }
}
