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

use FuzeWorks\Config;
use FuzeWorks\Configurator;
use FuzeWorks\Core;
use FuzeWorks\Exception\ConfiguratorException;
use FuzeWorks\Factory;
use FuzeWorks\iComponent;
use FuzeWorks\Logger;

/**
 * Class ConfiguratorTest.
 *
 * This test will test the Configurator class
 * @coversDefaultClass \FuzeWorks\Configurator
 */
class configuratorTest extends CoreTestAbstract
{

    /**
     * @var Configurator
     */
    protected Configurator $configurator;

    public function setUp(): void
    {
        $this->configurator = new Configurator;
        $this->configurator->setTempDirectory(dirname(__DIR__) . '/temp');
        $this->configurator->setLogDirectory(dirname(__DIR__) . '/temp');
        $this->configurator->setTimeZone('Europe/Amsterdam');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Core::$tempDir = dirname(__DIR__) . '/temp';
        Core::$logDir = dirname(__DIR__) . '/temp';
    }

    /**
     * @coversNothing
     */
    public function testGetConfiguratorClass()
    {
        $this->assertInstanceOf('FuzeWorks\Configurator', $this->configurator);
    }

    /**
     * @coversNothing
     */
    public function testCreateContainer()
    {
        $this->assertInstanceOf('FuzeWorks\Factory', $this->configurator->createContainer());
    }

    /* ---------------------------------- Components ------------------------------------------------ */

    /**
     * @depends testCreateContainer
     * @covers ::addComponent
     * @covers ::createContainer
     */
    public function testAddComponent()
    {
        // Load the component
        require_once 'test'.DS.'components'.DS.'TestAddComponent'.DS.'TestAddComponent.php';
        $component = new FuzeWorks\Component\TestComponent();
        $this->assertInstanceOf('FuzeWorks\Configurator', $this->configurator->addComponent($component));

        // Create container and test if component is added and has known properties
        $container = $this->configurator->createContainer();
        $this->assertTrue(property_exists($container, 'test'));
        $this->assertInstanceOf('FuzeWorks\Component\Test', $container->test);
        $this->assertEquals(5, $container->test->variable);
    }

    /**
     * @depends testAddComponent
     * @covers ::addComponent
     * @covers ::createContainer
     */
    public function testAddComponentClassByObject()
    {
        // Create object
        $object = $this->getMockBuilder(MockComponentClass::class)->getMock();
        $object->variable = 'value';

        // Create and add component
        $component = $this->getMockBuilder(MockComponent::class)->setMethods(['getClasses'])->getMock();
        $component->method('getClasses')->willReturn(['componentobject' => $object]);
        $this->assertInstanceOf('FuzeWorks\Configurator', $this->configurator->addComponent($component));

        // Create container and test for variable
        $container = $this->configurator->createContainer();
        $this->assertEquals('value', $container->componentobject->variable);
    }

    /**
     * @depends testAddComponent
     * @covers ::addComponent
     * @covers ::createContainer
     */
    public function testAddComponentFail()
    {
        // Load the component
        require_once 'test'.DS.'components'.DS.'TestAddComponentFail'.DS.'TestAddComponentFail.php';
        $component = new FuzeWorks\Component\TestAddComponentFailComponent;
        $this->configurator->addComponent($component);

        // Create container and fail
        $this->expectException(ConfiguratorException::class);
        $this->configurator->createContainer();
    }

    /* ---------------------------------- Directories ----------------------------------------------- */

    /**
     * @depends testCreateContainer
     * @covers ::setLogDirectory
     * @covers ::createContainer
     */
    public function testSetLogDirectory()
    {
        // Create mock filesystem
        $fs = vfsStream::setup('testSetLogDirectory');

        // Set the directory
        $this->assertInstanceOf('FuzeWorks\Configurator', $this->configurator->setLogDirectory(vfsStream::url('testSetLogDirectory')));

        // Create container and test if properly set
        $this->configurator->createContainer();
        $this->assertEquals(Core::$logDir, vfsStream::url('testSetLogDirectory'));

        // Create a log and write off to file
        Logger::log('Test log for the file');
        Logger::logLastRequest();

        // Assert if exist
        $this->assertTrue($fs->hasChild('fwlog_request.log'));
    }

    /**
     * @depends testSetLogDirectory
     * @covers ::setLogDirectory
     */
    public function testSetLogDirectoryNotDirectory()
    {
        // Set the directory
        $this->expectException(\FuzeWorks\Exception\InvalidArgumentException::class);
        $this->configurator->setLogDirectory('not_exist');
    }

    /**
     * @depends testCreateContainer
     * @covers ::setTempDirectory
     * @covers ::createContainer
     */
    public function testSetTempDirectory()
    {
        // Create mock filesystem
        vfsStream::setup('testSetTempDirectory');

        // Set the directory
        $this->assertInstanceOf('FuzeWorks\Configurator', $this->configurator->setTempDirectory(vfsStream::url('testSetTempDirectory')));

        // Create container and test if properly set
        $this->configurator->createContainer();
        $this->assertEquals(Core::$tempDir, vfsStream::url('testSetTempDirectory'));
    }

    /**
     * @depends testSetTempDirectory
     * @covers ::setTempDirectory
     */
    public function testSetTempDirectoryNotDirectory()
    {
        // Set the directory
        $this->expectException(\FuzeWorks\Exception\InvalidArgumentException::class);
        $this->configurator->setTempDirectory('not_exist');
    }

    /**
     * @depends testCreateContainer
     * @depends testAddComponent
     * @covers ::addComponent
     * @covers ::addDirectory
     * @covers ::createContainer
     */
    public function testAddComponentDirectory()
    {
        // Create mock filesystem
        vfsStream::setup('testAddComponentDirectory');

        // Add the component
        require_once 'test'.DS.'components'.DS.'TestAddComponentDirectory'.DS.'TestAddComponentDirectory.php';
        $component = new FuzeWorks\Component\TestAddComponentDirectoryComponent();
        $this->configurator->addComponent($component);

        // Add the directory
        $this->configurator->addDirectory(vfsStream::url('testAddComponentDirectory'), 'testaddcomponentdirectory');

        // Create container and test if component is added and has known properties
        $container = $this->configurator->createContainer();
        $this->assertTrue(property_exists($container, 'testaddcomponentdirectory'));
        $this->assertInstanceOf('FuzeWorks\Component\TestAddComponentDirectory', $container->testaddcomponentdirectory);
        $this->assertEquals(5, $container->testaddcomponentdirectory->variable);

        // Verify directory is set
        $this->assertEquals([vfsStream::url('testAddComponentDirectory')], $container->testaddcomponentdirectory->getComponentPaths());
    }

    /**
     * @depends testAddComponentDirectory
     * @covers ::addDirectory
     */
    public function testAddComponentDirectoryNotExist()
    {
        $this->expectException(\FuzeWorks\Exception\InvalidArgumentException::class);
        $this->configurator->addDirectory('not_exist', 'irrelevant');
    }

    /* ---------------------------------- Deferred Invocation --------------------------------------- */

    /**
     * @depends testAddComponent
     * @covers ::deferComponentClassMethod
     * @covers ::createContainer
     * @covers \FuzeWorks\DeferredComponentClass::invoke
     * @covers \FuzeWorks\DeferredComponentClass::isInvoked
     * @covers \FuzeWorks\DeferredComponentClass::getResult
     */
    public function testDeferComponentClassMethod()
    {
        // Create mocks
        $componentClass = $this->getMockBuilder(MockComponentClass::class)->setMethods(['update'])->getMock();
        $componentClass->expects($this->once())->method('update')->willReturn('result');
        $component = $this->getMockBuilder(MockComponent::class)->setMethods(['getClasses'])->getMock();
        $component->method('getClasses')->willReturn(['test' => $componentClass]);

        // Add the Component
        $this->configurator->addComponent($component);

        // Defer method
        $deferred = $this->configurator->deferComponentClassMethod('test', 'update');

        // Expect false before execution
        $this->assertFalse($deferred->isInvoked());
        $this->assertFalse($deferred->getResult());

        // Create container
        $this->configurator->createContainer();

        // Make assertions
        $this->assertTrue($deferred->isInvoked());
        $this->assertEquals('result', $deferred->getResult());
    }

    /**
     * @depends testDeferComponentClassMethod
     * @covers ::deferComponentClassMethod
     * @covers ::createContainer
     * @covers \FuzeWorks\DeferredComponentClass::invoke
     * @covers \FuzeWorks\DeferredComponentClass::isInvoked
     * @covers \FuzeWorks\DeferredComponentClass::getResult
     */
    public function testDeferComponentClassMethodWithCallback()
    {
        // Create mocks
        $componentClass = $this->getMockBuilder(MockComponentClass::class)->setMethods(['update'])->getMock();
        $componentClass->expects($this->once())->method('update')->with('some_argument')->willReturn('result');
        $component = $this->getMockBuilder(MockComponent::class)->setMethods(['getClasses'])->getMock();
        $component->method('getClasses')->willReturn(['test' => $componentClass]);

        // Add the Component
        $this->configurator->addComponent($component);

        // Defer method
        $deferred = $this->configurator->deferComponentClassMethod(
            'test',
            'update',
            function($result){
                $this->assertEquals('result', $result);
            },
            'some_argument'
            );

        // Create container
        $this->configurator->createContainer();

        // Make assertions
        $this->assertTrue($deferred->isInvoked());
        $this->assertEquals('result', $deferred->getResult());
    }

    /* ---------------------------------- Parameters ------------------------------------------------ */

    /**
     * @depends testCreateContainer
     * @covers ::setTimeZone
     */
    public function testSetTimezone()
    {
        // Set timezone and verify returns
        $this->assertInstanceOf('FuzeWorks\Configurator', $this->configurator->setTimeZone('Europe/Amsterdam'));

        // Test if properly set
        $this->assertEquals('Europe/Amsterdam', ini_get('date.timezone'));
    }

    /**
     * @depends testSetTimezone
     * @covers ::setTimeZone
     */
    public function testSetTimezoneInvalid()
    {
        $this->expectException(\FuzeWorks\Exception\InvalidArgumentException::class);
        $this->configurator->setTimeZone('Europe/Amsterdamned');
    }

    /**
     * @depends testCreateContainer
     * @covers ::setParameters
     * @covers ::createContainer
     */
    public function testSetParameter()
    {
        // Set a value that can be verified and test return object
        $this->assertInstanceOf('FuzeWorks\Configurator', $this->configurator->setParameters(['tempDir' => 'fake_directory']));

        // Create container and verify
        $this->configurator->createContainer();
        $this->assertEquals('fake_directory', Core::$tempDir);
    }

    /**
     * @depends testCreateContainer
     * @covers ::setConfigOverride
     * @covers ::createContainer
     */
    public function testSetConfigOverride()
    {
        // Set an override that can be verified
        $this->configurator->setConfigOverride('test', 'somekey', 'somevalue');

        // Create container
        $this->configurator->createContainer();

        // Verify that the variable is set in the Config class
        $this->assertEquals(['test' => ['somekey' => 'somevalue']], Config::$configOverrides);
    }

    /* ---------------------------------- Debugging ------------------------------------------------- */

    /**
     * @depends testCreateContainer
     * @covers ::enableDebugMode
     * @covers ::isDebugMode
     * @covers ::createContainer
     */
    public function testEnableDebugMode()
    {
        // Enable debug mode and verify return object
        $this->assertInstanceOf('FuzeWorks\Configurator', $this->configurator->enableDebugMode());

        // No match has been requested, so debugMode should be on now.
        $this->assertTrue($this->configurator->isDebugMode());

        // Set a debug address, all in this case; also verify return type
        $this->assertInstanceOf('FuzeWorks\Configurator', $this->configurator->setDebugAddress());

        // No match should be found. Verify that debug has been deactivated
        $this->assertFalse($this->configurator->isDebugMode());

        // Now let's enable it for testing purposes
        $this->configurator->enableDebugMode()->setDebugAddress('ALL');
        $this->assertTrue($this->configurator->isDebugMode());

        // Load the container and verify that tracy runs in debug mode
        $this->configurator->createContainer();
        $this->assertTrue(Logger::isEnabled());
    }

    /**
     * @depends testEnableDebugMode
     * @covers ::setDebugAddress
     * @covers ::enableDebugMode
     * @covers ::isDebugMode
     */
    public function testSetDebugAddress()
    {
        // First test return value
        $this->assertInstanceOf('FuzeWorks\Configurator', $this->configurator->setDebugAddress('ALL'));

        // Test address ALL and ENABLED
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress('ALL')->isDebugMode());

        // Test address NONE and ENABLED
        $this->assertFalse($this->configurator->enableDebugMode()->setDebugAddress('NONE')->isDebugMode());

        // Test custom addresses
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress([])->isDebugMode());
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress('192.168.1.1')->isDebugMode());

        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress([])->isDebugMode());

        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $this->assertFalse($this->configurator->enableDebugMode()->setDebugAddress([])->isDebugMode());
        $this->assertFalse($this->configurator->enableDebugMode()->setDebugAddress('192.168.1.1.0')->isDebugMode());
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress('192.168.1.1')->isDebugMode());
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress('a,192.168.1.1,b')->isDebugMode());
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress('a 192.168.1.1 b')->isDebugMode());

        // Test for HTTP_X_FORWARDED_FOR
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);
        $this->assertFalse($this->configurator->enableDebugMode()->setDebugAddress([])->isDebugMode());
        $this->assertFalse($this->configurator->enableDebugMode()->setDebugAddress('127.0.0.1')->isDebugMode());
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress(php_uname('n'))->isDebugMode());
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress([php_uname('n')])->isDebugMode());

        // Test for cookie based authentication
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $_COOKIE[Configurator::COOKIE_SECRET] = '*secret*';
        $this->assertFalse($this->configurator->enableDebugMode()->setDebugAddress([])->isDebugMode());
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress('192.168.1.1')->isDebugMode());
        $this->assertFalse($this->configurator->enableDebugMode()->setDebugAddress('abc@192.168.1.1')->isDebugMode());
        $this->assertTrue($this->configurator->enableDebugMode()->setDebugAddress('*secret*@192.168.1.1')->isDebugMode());

        $_COOKIE[Configurator::COOKIE_SECRET] = ['*secret*'];
        $this->assertFalse($this->configurator->enableDebugMode()->setDebugAddress('*secret*@192.168.1.1')->isDebugMode());

        // Unset
        unset($_COOKIE[Configurator::COOKIE_SECRET], $_SERVER['REMOTE_ADDR']);
    }
}

class MockComponent implements iComponent
{

    public function getName(): string
    {
        return 'MockComponent';
    }

    public function getClasses(): array
    {
        return [];
    }

    public function onAddComponent(Configurator $configurator): Configurator
    {
        return $configurator;
    }

    public function onCreateContainer(Factory $container): Factory
    {
        return $container;
    }
}

class MockComponentClass
{
    //public function update(){}
}