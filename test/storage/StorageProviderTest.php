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

namespace storage;

use FuzeWorks\Factory;
use FuzeWorks\Storage\iStorageProvider;
use FuzeWorks\Storage;
use PHPUnit\Framework\TestCase;

/**
 * Class ObjectStorageProviderTest
 *
 * @todo getIndex() method
 */
class StorageProviderTest extends TestCase
{

    private iStorageProvider $provider;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadProvider();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Always clear the provider at the end
        $this->provider->clear();
    }

    private function loadProvider()
    {
        /** @var Storage $objectStorageComponent */
        $objectStorageComponent = Factory::getInstance('storage');
        $this->provider = $objectStorageComponent->getStorage();
    }

    public function testFoundation()
    {
        $this->assertInstanceOf('\FuzeWorks\Storage\iStorageProvider', $this->provider);
    }

    /**
     * @depends testFoundation
     */
    public function testSave()
    {
        $testData = ['hello', 'world', 'foo' => 'bar'];

        // First assert it does not exist yet
        $this->assertNull($this->provider->getItem('testData'));
        $this->assertFalse($this->provider->hasItem('testData'));

        // Write the data
        $this->assertTrue($this->provider->save('testData', $testData));

        // Read the data
        $this->assertTrue($this->provider->hasItem('testData'));
        $this->assertEquals(['hello', 'world', 'foo' => 'bar'], $this->provider->getItem('testData'));
    }

    /**
     * @depends testSave
     */
    public function testDeleteItem()
    {
        $testData = ['o', 'm', 'g'];

        // First assert that the data does not exist yet
        $this->assertNull($this->provider->getItem('testDeleteData'));

        // Write the data
        $this->assertTrue($this->provider->save('testDeleteData', $testData));

        // Read the data
        $this->assertEquals(['o', 'm', 'g'], $this->provider->getItem('testDeleteData'));

        // Delete the data
        $this->assertTrue($this->provider->deleteItem('testDeleteData'));

        // And test that it is truly gone
        $this->assertNull($this->provider->getItem('testDeleteData'));
    }

    /**
     * @depends testDeleteItem
     */
    public function testDeleteItems()
    {
        $testData = 'lord';
        $testData2 = 'almighty';

        // First assert that the data does not exist yet
        $this->assertNull($this->provider->getItem('testDeleteData1'));
        $this->assertNull($this->provider->getItem('testDeleteData2'));

        // Write the data
        $this->assertTrue($this->provider->save('testDeleteData1', $testData));
        $this->assertTrue($this->provider->save('testDeleteData2', $testData2));

        // Read the data
        $this->assertEquals(
            ['testDeleteData1' => 'lord', 'testDeleteData2' => 'almighty'],
            $this->provider->getItems(['testDeleteData1', 'testDeleteData2'])
        );

        // Delete the data
        $this->assertTrue($this->provider->deleteItems(['testDeleteData1', 'testDeleteData2']));

        // And test that it is truly gone
        $this->assertNull($this->provider->getItem('testDeleteData1'));
        $this->assertNull($this->provider->getItem('testDeleteData2'));
    }

    /**
     * @depends testDeleteItems
     */
    public function testClear()
    {
        $testData = ['not', 'my', 'department'];

        // First assert it does not exist yet
        $this->assertNull($this->provider->getItem('testClearData'));

        $this->provider->save('testClearData', $testData);

        // Test that it can be read
        $this->assertEquals(['not', 'my', 'department'], $this->provider->getItem('testClearData'));

        // Then attempt to clean it
        $this->assertTrue($this->provider->clear());

        // And check that it cannot be read
        $this->assertNull($this->provider->getItem('testClearData'));
    }

    public function testItemNotExist()
    {
        $this->assertNull($this->provider->getItem('doesNotExist'));
    }

    public function testGetMultipleItems()
    {
        $testData1 = ['tao', 'te', 'ching'];
        $testData2 = ['plato', 'aristotle'];

        // First assert they do not exist
        $this->assertNull($this->provider->getItem('philo1'));
        $this->assertNull($this->provider->getItem('philo2'));
        $this->assertEquals(['philo1' => null, 'philo2' => null], $this->provider->getItems(['philo1', 'philo2']));

        // Then write both
        $this->assertTrue($this->provider->save('philo1', $testData1));
        $this->assertTrue($this->provider->save('philo2', $testData2));

        // Then read
        $this->assertEquals([
            'philo1' => ['tao', 'te', 'ching'],
            'philo2' => ['plato', 'aristotle']
        ], $this->provider->getItems(['philo1', 'philo2']));
    }

    public function testItemMetaData()
    {
        $testData = ['meine', 'gute'];
        $metaData = ['someKey' => 'someValue'];

        // First assert that the data does not exist
        $this->assertNull($this->provider->getItem('testData'));
        $this->assertNull($this->provider->getItemMeta('testData'));

        // Then save the data
        $this->assertTrue($this->provider->save('testData', $testData, $metaData));

        // Check the metaData
        $this->assertEquals(['someKey' => 'someValue'], $this->provider->getItemMeta('testData'));

        // Remove the key
        $this->provider->deleteItem('testData');
        $this->assertNull($this->provider->getItemMeta('testData'));
    }
}