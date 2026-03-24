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
use FuzeWorks\Core\Controller;
use FuzeWorks\Core\Event\ViewGetEvent;
use FuzeWorks\Core\Exception\NotFoundException;
use FuzeWorks\Core\Exception\ViewException;
use FuzeWorks\Core\View;
use FuzeWorks\Core\Views;

/**
 * Class ViewsTest
 * @coversDefaultClass \FuzeWorks\MVCR\Views
 */
class ViewsTest extends CoreTestAbstract
{

    /**
     * @var Views
     */
    protected Views $views;

    /**
     * @var Controller
     */
    protected $mockController;
    
    public function setUp(): void
    {
        $this->views = new Views();
        $this->views->addComponentPath(dirname(__DIR__) . DS . 'views');
        $this->mockController = $this->createStub(Controller::class);
    }

    /**
     * @covers ::get
     * @covers ::loadView
     */
    public function testGetViewFromClass()
    {
        // Create mock view
        $mockView = $this->createStub(View::class);
        $mockViewClass = get_class($mockView);
        class_alias($mockViewClass, $mockViewClass . 'StandardView');

        // Try and fetch this view from the Views class
        $this->assertInstanceOf($mockViewClass, $this->views->get($mockViewClass, $this->mockController, 'Standard', [], '\\'));
    }

    /**
     * Remove all listeners before the next test starts.
     */
    public function tearDown(): void
    {
        // Clear all events created by tests
        Events::$listeners = array();

        // Reset all config files
        Factory::getInstance()->config->discardConfigFiles();
    }

    /**
     * @depends testGetViewFromClass
     * @covers ::get
     * @covers ::loadView
     */
    public function testGetViewFromClassInvalidInstance()
    {
        // Create invalid mock
        $mockFakeView = $this->createStub(stdClass::class);
        $mockFakeViewClass = get_class($mockFakeView);
        class_alias($mockFakeViewClass, $mockFakeViewClass . 'StandardView');

        // Try and fetch
        $this->expectException(ViewException::class);
        $this->views->get($mockFakeViewClass, $this->mockController, 'Standard', [], '\\');
    }

    /**
     * @depends testGetViewFromClass
     * @covers ::get
     * @covers ::loadView
     */
    public function testGetViewFromClassDefaultNamespace()
    {
        // Create mock view
        $mockView = $this->createStub(View::class);
        $mockViewClass = get_class($mockView);
        class_alias($mockViewClass, '\Application\View\DefaultNamespaceStandardView');

        // Try and fetch
        $this->assertInstanceOf('\Application\View\DefaultNamespaceStandardView', $this->views->get('DefaultNamespace', $this->mockController));
    }

    /**
     * @depends testGetViewFromClass
     * @covers ::get
     * @covers ::loadView
     * @todo Implement. Mock constructor arguments doesn't work yet
     */
    public function testGetViewWithArguments()
    {
        // Can't be tested right now
        $this->assertTrue(true);
    }

    /**
     * @covers ::get
     */
    public function testGetViewInvalidName()
    {
        $this->expectException(ViewException::class);
        $this->views->get('', $this->mockController, 'Standard', [], '\\');
    }

    /**
     * @depends testGetViewFromClass
     * @covers ::get
     * @covers ::loadView
     */
    public function testGetViewFromFile()
    {
        $this->assertInstanceOf('\Application\View\TestGetViewStandardView', $this->views->get('TestGetView', $this->mockController));
    }

    /**
     * @depends testGetViewFromFile
     * @covers ::get
     * @covers ::loadView
     */
    public function testGetViewFromFileInvalidInstance()
    {
        $this->expectException(ViewException::class);
        $this->views->get('ViewInvalidInstance', $this->mockController);
    }

    /**
     * @depends testGetViewFromFile
     * @covers ::get
     * @covers ::loadView
     */
    public function testDifferentComponentPathPriority()
    {
        // Add the directories for this test
        $this->views->addComponentPath(dirname(__DIR__) . DS . 'views'.DS.'TestDifferentComponentPathPriority'.DS.'Lowest', Priority::LOWEST);
        $this->views->addComponentPath(dirname(__DIR__) . DS . 'views'.DS.'TestDifferentComponentPathPriority'.DS.'Highest', Priority::HIGHEST);

        // Load the view and assert it is the correct type
        $view = $this->views->get('TestDifferentComponentPathPriority', $this->mockController);
        $this->assertInstanceOf('\Application\View\TestDifferentComponentPathPriorityStandardView', $view);
        $this->assertEquals('highest', $view->type);

        // Clean up the test
        $this->views->setDirectories([]);
    }

    /**
     * @depends testGetViewFromFile
     * @covers ::get
     * @covers ::loadView
     */
    public function testGetSubdirectory()
    {
        $this->assertInstanceOf('\Application\View\TestGetSubdirectoryStandardView', $this->views->get('TestGetSubdirectory', $this->mockController));
    }

    /**
     * @depends testGetViewFromFile
     * @covers ::get
     * @covers ::loadView
     */
    public function testViewNotFound()
    {
        $this->expectException(NotFoundException::class);
        $this->views->get('NotFound', $this->mockController);
    }

    /**
     * @depends testGetViewFromClass
     * @covers ::get
     * @covers \FuzeWorks\MVCR\Event\ViewGetEvent::init
     */
    public function testViewGetEvent()
    {
        // Register listener
        Events::addListener(function($event){
            /** @var ViewGetEvent $event */
            $this->assertInstanceOf(ViewGetEvent::class, $event);
            $this->assertEquals('SomeViewName', $event->viewName);
            $this->assertInstanceOf('\FuzeWorks\Core\Controller', $event->controller);
            $this->assertEquals('Other', $event->viewType);
            $this->assertEquals([3 => ['some_path']], $event->viewPaths);
            $this->assertEquals('SomeNamespace', $event->namespace);
            $this->assertEquals(['Some Argument'], $event->arguments);
            $event->setCancelled(true);
        }, 'ViewGetEvent', Priority::NORMAL);

        $this->expectException(ViewException::class);
        $this->views->get('SomeViewName', $this->mockController, 'Other', ['some_path'], 'SomeNamespace', 'Some Argument');
    }

    /**
     * @depends testViewGetEvent
     * @covers ::get
     */
    public function testCancelGetView()
    {
        // Register listener
        Events::addListener(function($event){
            $event->setCancelled(true);
        }, 'ViewGetEvent', Priority::NORMAL);

        $this->expectException(ViewException::class);
        $this->views->get('SomeView', $this->mockController, 'Standard', [], '\\');
    }

    /**
     * @depends testViewGetEvent
     * @covers ::get
     * @covers ::loadView
     */
    public function testViewGetEventIntervene()
    {
        // Register listener
        Events::addListener(function($event){
            /** @var ViewGetEvent $event */
            $event->viewName = 'TestViewGetEventIntervene';
            $event->viewType = 'OtherType';
            $event->namespace = '\Some\Other\\';
        }, 'ViewGetEvent', Priority::NORMAL);

        $this->assertInstanceOf('\Some\Other\TestViewGetEventInterveneOtherTypeView', $this->views->get('Something_Useless', $this->mockController));
    }
}
