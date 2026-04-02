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

use FuzeWorks\Core\Database;
use FuzeWorks\Core\Factory;

/**
 * Class DatabaseTest
 *
 * Tests the Database component
 */
final class DatabaseTest extends CoreTestAbstract
{
    protected ?Database $database = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->database = Factory::getInstance()->databases;
    }

    public function testFoundation()
    {
        $this->assertInstanceOf(Database::class, $this->database);
    }

    /**
     * @depends testFoundation
     */
    public function testRegisterEngine()
    {
        // Create a mock engine
        $mockEngine = $this->createStub(\FuzeWorks\Core\iDatabaseEngine::class);
        $mockEngine->method('getName')->willReturn('MockEngine');

        // Register it
        $this->assertTrue($this->database->registerEngine($mockEngine));

        // Fetch it
        $fetchedEngine = $this->database->fetchEngine('MockEngine');
        $this->assertSame($mockEngine, $fetchedEngine);
    }

    /**
     * @depends testRegisterEngine
     */
    public function testFetchEngineNotFound()
    {
        $this->expectException(\FuzeWorks\Core\Exception\DatabaseException::class);
        $this->database->fetchEngine('NonExistentEngine');
    }

    /**
     * @depends testFoundation
     */
    public function testRegisterTableModel()
    {
        // Create a mock table model
        $mockTableModel = $this->createStub(\FuzeWorks\Core\iDatabaseTableModel::class);
        $mockTableModel->method('getName')->willReturn('MockTableModel');

        // Register it
        $this->assertTrue($this->database->registerTableModel($mockTableModel));

        // Fetch it
        $fetchedTableModel = $this->database->fetchTableModel('MockTableModel');
        $this->assertSame($mockTableModel, $fetchedTableModel);
    }

    /**
     * @depends testRegisterTableModel
     */
    public function testFetchTableModelNotFound()
    {
        $this->expectException(\FuzeWorks\Core\Exception\DatabaseException::class);
        $this->database->fetchTableModel('NonExistentTableModel');
    }

    // Additional specific tests for Database can be added here
}