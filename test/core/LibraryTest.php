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

use FuzeWorks\Core\Exception\LibraryException;
use FuzeWorks\Core\Factory;
use FuzeWorks\Core\Libraries;

/**
 * Class LibraryTest.
 *
 * Libraries testing suite, will test basic loading of and management of Libraries
 * @coversDefaultClass \FuzeWorks\Core\Libraries
 */
class LibraryTest extends CoreTestAbstract
{

    /**
     * @var Libraries
     */
    protected Libraries $libraries;

    public function setUp(): void
    {
        // Load new libraries class
        $this->libraries = new Libraries();

        // And then set all paths
        $this->libraries->setDirectories([3 => ['test'.DS.'libraries']]);
    }

    /**
     * @coversNothing
     */
    public function testLibrariesClass()
    {
        $this->assertInstanceOf('FuzeWorks\Core\Libraries', $this->libraries);
    }

    /* ---------------------------------- Load library from directories ------------------- */

    /**
     * @depends testLibrariesClass
     * @covers ::get
     * @covers ::initLibrary
     */
    public function testGetLibraryFromDirectory()
    {
        $this->assertInstanceOf('Application\Library\TestGetLibraryFromDirectory', $this->libraries->get('TestGetLibraryFromDirectory'));
    }

    /**
     * @depends testGetLibraryFromDirectory
     * @covers ::get
     * @covers ::initLibrary
     */
    public function testGetLibraryFromSubdirectory()
    {
        // Add test directory path
        $this->libraries->addComponentPath('test'.DS.'libraries'.DS.'TestGetLibraryFromSubdirectory');

        $this->assertInstanceOf('Application\Library\TestGetLibraryFromSubdirectory', $this->libraries->get('TestGetLibraryFromSubdirectory'));
    }

    /**
     * @depends testGetLibraryFromDirectory
     * @covers ::get
     * @covers ::initLibrary
     */
    public function testGetLibraryFromAltDirectory()
    {
        // Simple test of loading a library and checking if it exists
        $this->assertInstanceOf('Application\Library\TestGetLibraryFromAltDirectory',
            $this->libraries->get('TestGetLibraryFromAltDirectory', [], ['test'.DS.'libraries'.DS.'TestGetLibraryFromAltDirectory']));
    }

    /**
     * @depends testGetLibraryFromDirectory
     * @covers ::get
     * @covers ::initLibrary
     */
    public function testGetLibraryFail()
    {
        $this->expectException(LibraryException::class);
        $this->libraries->get('FailLoadLibrary');
    }

    /**
     * @depends testGetLibraryFromDirectory
     * @covers ::get
     * @covers ::initLibrary
     */
    public function testGetLibraryNoName()
    {
        $this->expectException(LibraryException::class);
        $this->libraries->get('');
    }

    /**
     * @depends testGetLibraryFromDirectory
     * @covers ::get
     * @covers ::initLibrary
     */
    public function testGetLibraryNoClass()
    {
        $this->expectException(LibraryException::class);
        $this->libraries->get('TestGetLibraryNoClass');
    }

    /**
     * @depends testGetLibraryFromDirectory
     * @covers ::get
     * @covers ::initLibrary
     */
    public function testGetLibraryParametersFromConfig()
    {
        // Prepare the config file
        $libraryName = 'TestGetLibraryParametersFromConfig';
        $libraryDir = 'test'.DS.'libraries'.DS.'TestGetLibraryParametersFromConfig';
        Factory::getInstance()->config->getConfig(strtolower($libraryName), [$libraryDir]);

        // Load the library
        $lib = $this->libraries->get('TestGetLibraryParametersFromConfig');
        $this->assertInstanceOf('Application\Library\TestGetLibraryParametersFromConfig', $lib);

        // And check the parameters
        $this->assertEquals(5, $lib->parameters['provided']);
    }

    /* ---------------------------------- Add libraries --------------------------------------------- */

    /**
     * @covers ::addLibraryObject
     * @covers ::get
     */
    public function testAddLibraryObject()
    {
        $z = new stdClass();
        $this->libraries->addLibraryObject('TestAddLibraryObject', $z);

        $this->assertEquals($z, $this->libraries->get('TestAddLibraryObject'));
    }

    /**
     * @covers ::addLibraryClass
     * @covers ::get
     */
    public function testAddLibraryClass()
    {
        require_once('test'.DS.'libraries'.DS.'TestAddLibraryClass'.DS.'TestAddLibraryClass.php');

        $this->libraries->addLibraryClass('LibraryClass', '\Custom\Spaces\TestAddLibraryClass');

        $this->assertInstanceOf('\Custom\Spaces\TestAddLibraryClass', $this->libraries->get('LibraryClass'));
    }

    /**
     * @depends testAddLibraryClass
     * @covers ::addLibraryClass
     */
    public function testAddLibraryClassFail()
    {
        $this->expectException(LibraryException::class);
        $this->libraries->addLibraryClass('LibraryClassFail', '\Case\Not\Exist');
    }

    /**
     * @depends testAddLibraryClass
     * @covers ::initLibrary
     */
    public function testAddLibraryWithAutoloader()
    {
        // First assert the extra class can't be autoloaded
        $this->assertFalse(class_exists('FuzeWorks\Test\TestAddLibraryWithAutoloader\SomeExtraClass', true));

        // Load the library and test the instance type
        $this->assertInstanceOf('Application\Library\TestAddLibraryWithAutoloader', $this->libraries->get('TestAddLibraryWithAutoloader'));

        // Afterwards test if the loader has been correctly added
        $this->assertTrue(class_exists('FuzeWorks\Test\TestAddLibraryWithAutoloader\SomeExtraClass', true));
    }

    /**
     * @depends testAddLibraryWithAutoloader
     * @covers ::initLibrary
     */
    public function testAddBadAutoloader()
    {
        $this->expectException(LibraryException::class);
        $this->assertInstanceOf('Application\Library\TestAddBadAutoloader', $this->libraries->get('TestAddBadAutoloader'));
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Factory::getInstance()->config->getConfig('error')->revert();
    }

}
