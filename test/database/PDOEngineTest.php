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
use FuzeWorks\Core\DatabaseProvider\PDOStatementWrapper;
use FuzeWorks\Core\Exception\DatabaseException;
use FuzeWorks\Core\Factory;
use FuzeWorks\Core\Database;

/**
 * Class PDOEngineTest
 *
 * Tests the PDOEngine
 */
final class PDOEngineTest extends CoreTestAbstract
{
    protected ?PDOEngine $engine = null;

    public function setUp(): void
    {
        parent::setUp();
        
        /** @var Database */
        $databases = Factory::getInstance()->databases;

        /** @var PDOEngine $this->engine */
        $this->engine = $databases->get("pdo");
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->engine->tearDown();
    }

    public function testFoundation()
    {
        $this->assertInstanceOf(PDOEngine::class, $this->engine);
    }

    /**
     * @depends testFoundation
     */
    public function testGetName()
    {
        $this->assertEquals('pdo', $this->engine->getName());
    }

    /**
     * @depends testFoundation
     */
    public function testDefaultSetup()
    {
        // By default, the engine should be setup
        $this->assertTrue($this->engine->isSetup());

        // Check that the connection description matches the expected DSN
        $host = getenv("PDO_DSN");
        $this->assertEquals($host, $this->engine->getConnectionDescription());
    }

    /**
     * @depends testFoundation
     */
    public function testSetupWithValidParameters()
    {
        // Use a SQLite in-memory database for testing
        $parameters = [
            'dsn' => 'sqlite::memory:',
            'username' => 'hello',
            'password' => 'world'
        ];

        $this->assertTrue($this->engine->setUp($parameters));
        $this->assertTrue($this->engine->isSetup());
        $this->assertEquals('sqlite::memory:', $this->engine->getConnectionDescription());  
    }

    /**
     * @depends testFoundation
     */
    public function testSetupWithInvalidParameters()
    {
        $parameters = [
            'dsn' => 'invalid:dsn',
            'username' => '',
            'password' => ''
        ];

        $this->expectException(DatabaseException::class);
        $this->engine->setUp($parameters);
    }

    /**
     * @depends testFoundation
     */
    public function testEmptyEngine()
    {
        $engine = new PDOEngine();
        $this->assertFalse($engine->isSetup());
        $this->assertEquals('none', $engine->getConnectionDescription());
    }

    /**
     * @depends testSetupWithValidParameters
     */
    public function testTearDown()
    {
        $this->assertTrue($this->engine->isSetup());
        $this->assertTrue($this->engine->tearDown());
        $this->assertFalse($this->engine->isSetup());
    }

    /**
     * @depends testSetupWithValidParameters
     */
    public function testQuery()
    {
        $parameters = [
            'dsn' => 'sqlite::memory:',
            'username' => '',
            'password' => ''
        ];

        $this->engine->setUp($parameters);
        $stmt = $this->engine->query('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');

        $this->assertInstanceOf(PDOStatement::class, $stmt);

        // Check that the query was logged
        $queries = $this->engine->getQueries();
        $this->assertCount(1, $queries);
        $this->assertEquals('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)', $queries[0]['queryString']);
    }

    /**
     * @depends testQuery
     */
    public function testEmptyQuery()
    {
        $this->expectException(DatabaseException::class);
        $this->engine->query('');
    }

    /**
     * @depends testSetupWithValidParameters
     */
    public function testPrepare()
    {
        $parameters = [
            'dsn' => 'sqlite::memory:',
            'username' => '',
            'password' => ''
        ];

        $this->engine->setUp($parameters);
        $stmt = $this->engine->prepare('SELECT 1');

        $this->assertInstanceOf(PDOStatementWrapper::class, $stmt);
    }

    /**
     * @depends testSetupWithValidParameters
     */
    public function testTransactionMethods()
    {
        $parameters = [
            'dsn' => 'sqlite::memory:',
            'username' => '',
            'password' => ''
        ];

        $this->engine->setUp($parameters);

        // Test transaction start
        $this->assertTrue($this->engine->transactionStart());

        // Test transaction commit
        $this->assertTrue($this->engine->transactionCommit());

        // Test transaction rollback (after start)
        $this->engine->transactionStart();
        $this->assertTrue($this->engine->transactionRollback());
    }

    /**
     * @depends testQueries
     */
    public function testLogQueries()
    {
        $this->assertEmpty($this->engine->getQueries());
        $this->engine->query('SELECT 1');
        $queries = $this->engine->getQueries();
        $this->assertCount(1, $queries);
        $this->assertEquals(
            [
                'queryString' => 'SELECT 1',
                'queryData' => 0,
                'queryTimings' => $queries[0]['queryTimings'], // We can't
                'queryError' => []
        ], $queries[0]);
    }

    /**
     * @depends testQuery
     */
    public function testMagicCall()
    {
        $this->engine->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $this->engine->getAttribute(PDO::ATTR_ERRMODE));
    }

}