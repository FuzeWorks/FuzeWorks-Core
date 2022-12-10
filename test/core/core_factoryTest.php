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

use FuzeWorks\Factory;
use FuzeWorks\Exception\FactoryException;

/**
 * Class FactoryTest.
 *
 * Will test the FuzeWorks Factory.
 * @coversDefaultClass \FuzeWorks\Factory
 */
class factoryTest extends CoreTestAbstract
{

    /**
     * @covers ::getInstance
     */
    public function testCanLoadFactory()
    {
        $this->assertInstanceOf('FuzeWorks\Factory', Factory::getInstance());
    }

    /**
     * @covers ::getInstance
     */
    public function testGetInstance()
    {
        // Add the mock
        $mock = $this->getMockBuilder(MockFactory::class)->getMock();
        Factory::getInstance()->setInstance('Mock', $mock);

        // First test a global getInstance Factory
        $this->assertInstanceOf('\FuzeWorks\Factory', Factory::getInstance());

        // Second, test retrieving a component
        $this->assertInstanceOf(get_class($mock), Factory::getInstance('Mock'));
    }

    /**
     * @depends testGetInstance
     * @covers ::getInstance
     */
    public function testGetInstanceNotFound()
    {
        $this->expectException(FactoryException::class);
        Factory::getInstance('NotFound');
    }

    /**
     * @depends testCanLoadFactory
     * @covers ::getInstance
     */
    public function testLoadSameInstance()
    {
        $this->assertSame(Factory::getInstance(), Factory::getInstance());
    }

    /**
     * @depends testCanLoadFactory
     * @covers ::getInstance
     * @covers ::cloneInstance
     */
    public function testLoadDifferentInstance()
    {
        // Add the mock
        $mock = $this->getMockBuilder(MockFactory::class)->getMock();
        Factory::getInstance()->setInstance('Mock', $mock);

        // First a situation where one is the shared instance and one is a cloned instance
        $a = Factory::getInstance('Mock');
        $b = Factory::cloneInstance('Mock');
        $this->assertInstanceOf(get_class($mock), $a);
        $this->assertInstanceOf(get_class($mock), $b);
        $this->assertNotSame($a,$b);

        // And a situation where both are cloned instances
        $a = Factory::cloneInstance('Mock');
        $b = Factory::cloneInstance('Mock');
        $this->assertInstanceOf(get_class($mock), $a);
        $this->assertInstanceOf(get_class($mock), $b);
        $this->assertNotSame($a,$b);
    }

    /**
     * @depends testCanLoadFactory
     * @covers ::getInstance
     * @covers ::setInstance
     */
    public function testObjectsSameInstance()
    {
        // Create mock
        $mock = $this->getMockBuilder(MockFactory::class)->setMethods(['mockListener'])->getMock();

        // Test not set
        $this->assertFalse(isset(Factory::getInstance()->mock));

        // Same instance factories
        /** @var Factory $factory1 */
        /** @var Factory $factory2 */
        $factory1 = Factory::getInstance()->setInstance('Mock', $mock);
        $factory2 = Factory::getInstance()->setInstance('Mock', $mock);

        // Return the mocks
        $this->assertSame($factory1->mock, $factory2->mock);

        // Different instance factories
        $factory3 = Factory::getInstance()->setInstance('Mock', $mock);
        $factory4 = Factory::getInstance()->setInstance('Mock', $mock);

        // Return the mocks
        $this->assertSame($factory3->mock, $factory4->mock);
    }

    /**
     * @depends testObjectsSameInstance
     * @covers ::getInstance
     * @covers ::setInstance
     * @covers ::cloneInstance
     */
    public function testObjectsDifferentInstance()
    {
        // Create mock
        $mock = $this->getMockBuilder(MockFactory::class)->getMock();

        // Same instance factories
        $factory1 = Factory::getInstance()->setInstance('Mock', $mock);
        $factory2 = Factory::getInstance()->setInstance('Mock', $mock);

        // Clone the instance in factory2
        $factory2mock = $factory2->cloneInstance('Mock');

        // Should be true, since both Factories use the same Mock instance
        $this->assertSame($factory1->mock, $factory2mock);

        // Different instance factories
        $factory3 = Factory::getInstance()->setInstance('Mock', $mock);
        $factory4 = Factory::getInstance()->setInstance('Mock', $mock);

        // Should be same for now
        $this->assertSame($factory3->mock, $factory4->mock);

        // Clone the instance in factory4
        $factory4mock = $factory4->cloneInstance('Mock', true);

        // Should be false, since both Factories use a different Mock instance
        $this->assertNotSame($factory3->mock, $factory4mock);
    }

    /**
     * @depends testCanLoadFactory
     * @covers ::cloneInstance
     */
    public function testCloneInstanceWrongClassname()
    {
        // Get factory
        $factory = new Factory;

        // Attempt
        $this->expectException(FactoryException::class);
        $factory->cloneInstance('fake');
    }

    /**
     * @depends testCanLoadFactory
     * @covers ::getInstance
     * @covers ::newInstance
     */
    public function testNewFactoryInstance()
    {
        // Load the different factories
        $factory = new Factory();
        $factory2 = Factory::getInstance();

        // Test if the objects are different factory instances
        $this->assertNotSame($factory, $factory2);

        // And test if all ClassInstances are the same
        $this->assertSame($factory->config, $factory2->config);
        $this->assertSame($factory->logger, $factory2->logger);
        $this->assertSame($factory->events, $factory2->events);
        $this->assertSame($factory->libraries, $factory2->libraries);
        $this->assertSame($factory->helpers, $factory2->helpers);
        $this->assertSame($factory->storage, $factory2->storage);

        // And test when changing one classInstance
        $factory->newInstance('Helpers');
        $this->assertNotSame($factory->helpers, $factory2->helpers);
    }

    /**
     * @depends testNewFactoryInstance
     * @covers ::newInstance
     */
    public function testFactoryNewInstanceNotExist()
    {
        // Load the factory
        $factory = new Factory;

        // First, it does not exist
        $this->expectException(FactoryException::class);
        $factory->newInstance('fake');
    }

    /**
     * @depends testNewFactoryInstance
     * @covers ::newInstance
     */
    public function testFactoryNewInstanceWrongNamespace()
    {
        // Load the factory
        $factory = new Factory;

        // Second, it just fails
        $this->expectException(FactoryException::class);
        $factory->newInstance('helpers', 'Test\\');
    }

    /**
     * @depends testNewFactoryInstance
     * @covers ::setInstance
     * @covers ::removeInstance
     */
    public function testRemoveInstance()
    {
        // Load the factory
        $factory = new Factory;

        // Create the object
        $object = new MockObject;

        // Add it to the factory
        $factory->setInstance('test', $object);

        // Test if it is there
        $this->assertObjectHasAttribute('test', $factory);
        $this->assertSame($object, $factory->test);

        // Now remove it
        $factory->removeInstance('test');

        // Assert that it's gone
        $this->assertObjectNotHasAttribute('test', $factory);
    }

    /**
     * @depends testRemoveInstance
     * @covers ::removeInstance
     */
    public function testRemoveInstanceNotExist()
    {
        // Load the factory
        $factory = new Factory;

        // Test
        $this->expectException(FactoryException::class);
        $factory->removeInstance('fake');
    }

    /**
     * @depends testCanLoadFactory
     * @covers ::instanceIsset
     * @covers ::setInstance
     */
    public function testInstanceIsset()
    {
        // Load the factory
        $factory = new Factory;

        // Test if not set and add instance
        $this->assertFalse($factory->instanceIsset('test'));
        $factory->setInstance('test', 5);

        // Test if isset and value
        $this->assertTrue($factory->instanceIsset('test'));
        $this->assertEquals(5, $factory->test);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $factory = Factory::getInstance();
        if (isset($factory->mock))
           $factory->removeInstance('mock');
    }

}

class MockFactory {

}

class MockObject {

}