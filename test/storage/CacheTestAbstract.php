<?php
/**
 * FuzeWorks ObjectStorage Component.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2020 i15
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
 * @author    i15
 * @copyright Copyright (c) 2013 - 2020, i15. (https://i15.nl)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @since Version 1.3.0
 *
 * @version Version 1.3.0
 */

use FuzeWorks\Core\Cache;
use Psr\SimpleCache\CacheInterface;

abstract class CacheTestAbstract extends CoreTestAbstract
{

    protected ?CacheInterface $cache = null;

    abstract public function loadProvider(): CacheInterface;

    public function setUp(): void
    {
        parent::setUp();
        $this->cache = $this->loadProvider();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Always clear the cache after every test
        $this->cache->clear();
    }

    public function testFoundation()
    {
        $this->assertInstanceOf(CacheInterface::class, $this->cache);
        $this->assertInstanceOf(Cache::class, $this->cache);
    }

    /**
     * @depends testFoundation
     */
    public function testSetGetAndHas()
    {
        $testData = ['hello', 'world'];

        // First check the data isn't there
        $this->assertFalse($this->cache->has('testData'));

        // Then write it
        $this->assertTrue($this->cache->set('testData', $testData));

        // Assert it is there and check its contents
        $this->assertTrue($this->cache->has('testData'));
        $this->assertEquals(['hello', 'world'], $this->cache->get('testData'));
    }

    /**
     * @depends testSetGetAndHas
     */
    public function testGetDefaultValue()
    {
        // Verify that no value exists
        $this->assertFalse($this->cache->has('testData'));
        $this->assertNull($this->cache->get('testData'));

        // And check if the default value is returned
        $this->assertEquals('default!', $this->cache->get('testData', 'default!'));
    }

    /**
     * @depends testSetGetAndHas
     */
    public function testDeleteValue()
    {
        // Verify that none exist
        $this->assertFalse($this->cache->has('testData'));

        // Write some data
        $this->assertTrue($this->cache->set('testData', 'someValue'));
        $this->assertEquals('someValue', $this->cache->get('testData'));
        $this->assertTrue($this->cache->has('testData'));

        // Delete it
        $this->assertTrue($this->cache->delete('testData'));
        $this->assertFalse($this->cache->has('testData'));
    }

    /**
     * @depends testDeleteValue
     */
    public function testClear()
    {
        // Write some data
        $this->assertTrue($this->cache->set('testData1', 'value1'));
        $this->assertTrue($this->cache->set('testData2', 'value2'));

        // Then clear it off
        $this->assertTrue($this->cache->clear());
        $this->assertFalse($this->cache->has('testData1'));
        $this->assertFalse($this->cache->has('testData2'));
    }

    /**
     * @depends testDeleteValue
     */
    public function testMultiple()
    {
        // First check that none of the variables exist
        $this->assertFalse($this->cache->has('testData1'));
        $this->assertFalse($this->cache->has('testData2'));
        $this->assertFalse($this->cache->has('testData3'));

        // With a get multiple, and default
        $this->assertEquals([
            'testData1' => 'default',
            'testData2' => 'default',
            'testData3' => 'default'
        ], $this->cache->getMultiple(['testData1', 'testData2', 'testData3'], 'default'));

        // Write multiple
        $this->assertTrue($this->cache->setMultiple([
            'testData1' => 'value1',
            'testData2' => 'value2',
            'testData3' => 'value3'
        ]));

        // Test the contents
        $this->assertEquals([
            'testData1' => 'value1',
            'testData2' => 'value2',
            'testData3' => 'value3'
        ], $this->cache->getMultiple(['testData1', 'testData2', 'testData3'], 'default'));

        // And also delete them all
        $this->assertTrue($this->cache->deleteMultiple(['testData1', 'testData2', 'testData3']));
        $this->assertFalse($this->cache->has('testData1'));
        $this->assertFalse($this->cache->has('testData2'));
        $this->assertFalse($this->cache->has('testData3'));
    }

    /**
     * @depends testSetGetAndHas
     */
    public function testTTL()
    {
        $testData = ['hello', 'world'];

        // First check the data isn't there
        $this->assertFalse($this->cache->has('testData'));

        // Then write it
        $this->assertTrue($this->cache->set('testData', $testData, 1));

        // Assert it is there and check its contents
        $this->assertTrue($this->cache->has('testData'));
        $this->assertEquals(['hello', 'world'], $this->cache->get('testData'));

        // Then wait 2 secs
        sleep(2);

        // And check again
        $this->assertFalse($this->cache->has('testData'));
    }

}