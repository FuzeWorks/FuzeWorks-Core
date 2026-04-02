<?php
/**
 * FuzeWorks Database Component.
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
 * IN NO CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
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

use FuzeWorks\Core\DatabaseProvider\MongoEngine;
use FuzeWorks\Core\Model\MongoTableModel;
use FuzeWorks\Core\Model\TableModelResult;
use FuzeWorks\Core\Exception\DatabaseException;
use FuzeWorks\Core\Factory;
use FuzeWorks\Core\Database;

/**
 * Class MongoTableModelTest
 */
final class MongoTableModelTest extends CoreTestAbstract
{
    protected ?MongoEngine $engine = null;
    protected ?MongoTableModel $tableModel = null;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Database */
        $databases = Factory::getInstance()->databases;

        /** @var MongoEngine $this->engine */
        $this->engine = $databases->get("mongodb");

        // Set up the table model
        $this->tableModel = $databases->getTableModel("testdb.testcollection", "mongodb");

        // Ensure clean collection
        $this->engine->selectDatabase('testdb')->selectCollection('testcollection')->drop();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->engine->tearDown();
    }

    public function testFoundation()
    {
        $this->assertInstanceOf(MongoTableModel::class, $this->tableModel);
    }

    /**
     * @depends testFoundation
     */
    public function testCreateReadUpdateDeleteFlow()
    {
        $inserted = $this->tableModel->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $this->assertEquals(1, $inserted);

        $result = $this->tableModel->read();
        $this->assertInstanceOf(TableModelResult::class, $result);

        $rows = $result->toArray();
        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);

        $grouped = $result->group('name')->toArray();
        $this->assertArrayHasKey('Alice', $grouped);

        $updated = $this->tableModel->update(['email' => 'alice2@example.com'], ['name' => 'Alice']);
        $this->assertGreaterThanOrEqual(1, $updated);

        $readAfterUpdate = $this->tableModel->read(['name' => 'Alice']);
        $this->assertSame('alice2@example.com', $readAfterUpdate->toArray()[0]['email']);

        $deleted = $this->tableModel->delete(['name' => 'Alice']);
        $this->assertGreaterThanOrEqual(1, $deleted);

        $this->assertEmpty($this->tableModel->read(['name' => 'Alice'])->toArray());
    }

    public function testCrudThrowsNoSetup()
    {
        $model = new MongoTableModel();
        $this->expectException(DatabaseException::class);
        $model->getEngine();
    }

    public function testTransactionMethods() {
        $this->assertFalse($this->tableModel->transactionStart());
        $this->assertFalse($this->tableModel->transactionEnd());
        $this->assertFalse($this->tableModel->transactionCommit());
        $this->assertFalse($this->tableModel->transactionRollback());
    }
}
