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

use FuzeWorks\Core\DatabaseProvider\PDOEngine;
use FuzeWorks\Core\Model\PDOTableModel;
use FuzeWorks\Core\Model\TableModelResult;
use FuzeWorks\Core\Exception\DatabaseException;
use FuzeWorks\Core\Factory;
use FuzeWorks\Core\Database;

/**
 * Class PDOTableModelTest
 */
final class PDOTableModelTest extends CoreTestAbstract
{
    protected ?PDOEngine $engine = null;
    protected ?PDOTableModel $tableModel = null;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Database */
        $databases = Factory::getInstance()->databases;
        
        /** @var PDOEngine $this->engine */
        $this->engine = $databases->get("pdo");

        // Prepare a sample table for the model
        $this->engine->query('CREATE TABLE people (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)');

        // Set up the table model
        $this->tableModel = $databases->getTableModel("people", "pdo");
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->engine->tearDown();
    }

    public function testFoundation()
    {
        $this->assertInstanceOf(PDOTableModel::class, $this->tableModel);
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
        $this->assertEquals('Alice', $rows[0]['name']);

        $grouped = $result->group('name')->toArray();
        $this->assertArrayHasKey('Alice', $grouped);

        $updated = $this->tableModel->update(['name' => 'Alice2'], ['id' => 1]);
        $this->assertEquals(1, $updated);

        $readAfterUpdate = $this->tableModel->read(['id' => 1]);
        $this->assertEquals('Alice2', $readAfterUpdate->toArray()[0]['name']);

        $deleted = $this->tableModel->delete(['id' => 1]);
        $this->assertEquals(1, $deleted);

        $emptyResult = $this->tableModel->read(['id' => 1]);
        $this->assertEmpty($emptyResult->toArray());
    }

    public function testCrudThrowsWhenNoSetup() {
        $model = new PDOTableModel();
        $this->expectException(DatabaseException::class);
        $model->getEngine();
    }

    public function testTransactionMethods()
    {
        $this->assertTrue($this->tableModel->getEngine()->transactionStart());
        $this->assertTrue($this->tableModel->getEngine()->transactionRollback());
    }
}
