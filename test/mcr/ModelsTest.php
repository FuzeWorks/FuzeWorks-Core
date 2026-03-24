<?php
/**
 * FuzeWorks Framework MVCR Component.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2018 TechFuze
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
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 1.2.0
 *
 * @version Version 1.2.0
 */

use FuzeWorks\Core\Events;
use FuzeWorks\Core\Factory;
use FuzeWorks\Core\Priority;
use FuzeWorks\Core\Event\ModelGetEvent;
use FuzeWorks\Core\Exception\ModelException;
use FuzeWorks\Core\Exception\NotFoundException;
use FuzeWorks\Core\Model;
use FuzeWorks\Core\Models;

/**
 * Class ModelsTest
 * @coversDefaultClass \FuzeWorks\MVCR\Models
 */
class ModelsTest extends CoreTestAbstract
{

    /**
     * @var Models
     */
    protected Models $models;

    public function setUp(): void
    {
        $this->models = new Models();
        $this->models->addComponentPath(dirname(__DIR__) . DS . 'models');
    }

    /**
     * Remove all listeners before the next test starts.
     */
    public function tearDown(): void
    {
        // Clear all events created by tests
        Events::$listeners = [];

        // Reset all config files
        Factory::getInstance()->config->discardConfigFiles();
    }

    /**
     * @covers ::get
     * @covers ::loadModel
     */
    public function testGetModelFromClass()
    {
        // Create mock model
        $mockModel = $this->createStub(Model::class);
        $mockModelClass = get_class($mockModel);
        class_alias($mockModelClass, $mockModelClass . 'Model');

        // Try and fetch this model from the Models class
        $this->assertInstanceOf($mockModelClass, $this->models->get($mockModelClass, [], '\\'));
    }

    /**
     * @depends testGetModelFromClass
     * @covers ::get
     * @covers ::loadModel
     */
    public function testGetModelFromClassInvalidInstance()
    {
        // Create invalid mock
        $mockFakeModel = $this->createStub(stdClass::class);
        $mockFakeModelClass = get_class($mockFakeModel);
        class_alias($mockFakeModelClass, $mockFakeModelClass . 'Model');

        // Try and fetch
        $this->expectException(ModelException::class);
        $this->models->get($mockFakeModelClass, [], '\\');
    }

    /**
     * @depends testGetModelFromClass
     * @covers ::get
     * @covers ::loadModel
     */
    public function testGetModelFromClassDefaultNamespace()
    {
        // Create mock model
        $mockModel = $this->createStub(Model::class);
        $mockModelClass = get_class($mockModel);
        class_alias($mockModelClass, '\Application\Model\DefaultNamespaceModel');

        // Try and fetch
        $this->assertInstanceOf('\Application\Model\DefaultNamespaceModel', $this->models->get('DefaultNamespace'));
    }

    /**
     * @depends testGetModelFromClass
     * @covers ::get
     * @covers ::loadModel
     * @todo Implement. Mock constructor arguments doesn't work yet
     */
    public function testGetModelWithArguments()
    {
        // Can't be tested right now
        $this->assertTrue(true);
    }

    /**
     * @covers ::get
     */
    public function testGetModelInvalidName()
    {
        $this->expectException(ModelException::class);
        $this->models->get('', [], '\\');
    }

    /**
     * @depends testGetModelFromClass
     * @covers ::get
     * @covers ::loadModel
     */
    public function testGetModelFromFile()
    {
        $this->assertInstanceOf('\Application\Model\TestGetModelModel', $this->models->get('TestGetModel'));
    }

    /**
     * @depends testGetModelFromFile
     * @covers ::get
     * @covers ::loadModel
     */
    public function testGetModelFromFileInvalidInstance()
    {
        $this->expectException(ModelException::class);
        $this->models->get('ModelInvalidInstance');
    }

    /**
     * @depends testGetModelFromFile
     * @covers ::get
     * @covers ::loadModel
     */
    public function testDifferentComponentPathPriority()
    {
        // Add the directories for this test
        $this->models->addComponentPath(dirname(__DIR__) . DS . 'models'.DS.'TestDifferentComponentPathPriority'.DS.'Lowest', Priority::LOWEST);
        $this->models->addComponentPath(dirname(__DIR__) . DS . 'models'.DS.'TestDifferentComponentPathPriority'.DS.'Highest', Priority::HIGHEST);

        // Load the model and assert it is the correct type
        $model = $this->models->get('TestDifferentComponentPathPriority');
        $this->assertInstanceOf('\Application\Model\TestDifferentComponentPathPriorityModel', $model);
        $this->assertEquals('highest', $model->type);

        // Clean up the test
        $this->models->setDirectories([]);
    }

    /**
     * @depends testGetModelFromFile
     * @covers ::get
     * @covers ::loadModel
     */
    public function testGetSubdirectory()
    {
        $this->assertInstanceOf('\Application\Model\TestGetSubdirectoryModel', $this->models->get('TestGetSubdirectory'));
    }

    /**
     * @depends testGetModelFromFile
     * @covers ::get
     * @covers ::loadModel
     */
    public function testModelNotFound()
    {
        $this->expectException(NotFoundException::class);
        $this->models->get('NotFound');
    }

    /**
     * @depends testGetModelFromClass
     * @covers ::get
     * @covers \FuzeWorks\MVCR\Event\ModelGetEvent::init
     */
    public function testModelGetEvent()
    {
        // Register listener
        Events::addListener(function($event){
            /** @var ModelGetEvent $event */
            $this->assertInstanceOf(ModelGetEvent::class, $event);
            $this->assertEquals('SomeModelName', $event->modelName);
            $this->assertEquals([3 => ['some_path']], $event->modelPaths);
            $this->assertEquals('SomeNamespace', $event->namespace);
            $this->assertEquals(['Some Argument'], $event->arguments);
            $event->setCancelled(true);
        }, 'ModelGetEvent', Priority::NORMAL);

        $this->expectException(ModelException::class);
        $this->models->get('SomeModelName', ['some_path'], 'SomeNamespace', 'Some Argument');
    }

    /**
     * @depends testModelGetEvent
     * @covers ::get
     */
    public function testCancelGetModel()
    {
        // Register listener
        Events::addListener(function($event){
            $event->setCancelled(true);
        }, 'ModelGetEvent', Priority::NORMAL);

        $this->expectException(ModelException::class);
        $this->models->get('SomeModel', [], '\\');
    }

    /**
     * @depends testModelGetEvent
     * @covers ::get
     * @covers ::loadModel
     */
    public function testModelGetEventIntervene()
    {
        // Register listener
        Events::addListener(function($event){
            /** @var ModelGetEvent $event */
            $event->modelName = 'TestModelGetEventIntervene';
            $event->namespace = '\Some\Other\\';
        }, 'ModelGetEvent', Priority::NORMAL);

        $this->assertInstanceOf('\Some\Other\TestModelGetEventInterveneModel', $this->models->get('Something_Useless'));
    }

}
