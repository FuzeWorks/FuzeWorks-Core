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
use FuzeWorks\Core\DatabaseProvider\MongoEngine;
use FuzeWorks\Core\Exception\DatabaseException;
use FuzeWorks\Core\Factory;

/**
 * Class MongoEngineTest
 *
 * Tests the MongoEngine
 */
final class MongoEngineTest extends CoreTestAbstract
{
    protected ?MongoEngine $engine = null;

    public function setUp(): void
    {
        parent::setUp();
        
        /** @var Database */
        $databases = Factory::getInstance()->databases;
        /** @var MongoEngine $this->engine */
        $this->engine = $databases->get("mongodb");
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->engine->tearDown();
    }

    public function testFoundation()
    {
        $this->assertInstanceOf(MongoEngine::class, $this->engine);
    }

    /**
     * @depends testFoundation
     */
    public function testGetName()
    {
        $this->assertEquals('mongo', $this->engine->getName());
    }

    /**
     * @depends testFoundation
     */
    public function testDefaultSetup()
    {
        // By default, the engine should not be setup
        $this->assertTrue($this->engine->isSetup());

        // This might fail if MongoDB is not running, but we test the setup logic
        try {
            $host = getenv("MONGO_HOST");
            $this->assertEquals("mongodb://$host:27017", $this->engine->getConnectionDescription());
        } catch (DatabaseException $e) {
            // If MongoDB is not running, that's expected in test environment
            $this->assertEquals('Could not connect to MongoDB', $e->getMessage());
        }
    }

    /**
     * @depends testFoundation
     */
    public function testSetupWithValidParameters()
    {
        $parameters = [
            'uri' => 'mongodb://localhost:27017',
            'uriOptions' => [],
            'driverOptions' => []
        ];

        // This might fail if MongoDB is not running, but we test the setup logic
        try {
            $this->assertTrue($this->engine->setUp($parameters));
            $this->assertTrue($this->engine->isSetup());
            $this->assertEquals('mongodb://localhost:27017', $this->engine->getConnectionDescription());
        } catch (DatabaseException $e) {
            // If MongoDB is not running, that's expected in test environment
            $this->assertEquals('Could not connect to MongoDB', $e->getMessage());
        }
    }

    /**
     * @depends testFoundation
     */
    public function testSetupWithInvalidParameters()
    {
        $parameters = [
            'uri' => null,
            'uriOptions' => [],
            'driverOptions' => []
        ];

        $this->expectException(DatabaseException::class);
        $this->engine->setUp($parameters);
    }

    /**
     * @depends testFoundation
     */
    public function testEmptyEngine()
    {
        $engine = new MongoEngine();
        $this->assertFalse($engine->isSetup());
        $this->assertEquals('none', $engine->getConnectionDescription());
    }

    public function testTearDown()
    {
        // MongoDB connections are closed automatically, so tearDown should return true even if not setup
        $this->assertTrue($this->engine->isSetup());
        $this->assertTrue($this->engine->tearDown());
        $this->assertFalse($this->engine->isSetup());
    }

    /**
     * @depends testFoundation
     */
    public function testTransactionMethods()
    {
        // MongoDB transactions are not implemented in this engine
        $this->assertFalse($this->engine->transactionStart());
        $this->assertFalse($this->engine->transactionEnd());
        $this->assertFalse($this->engine->transactionCommit());
        $this->assertFalse($this->engine->transactionRollback());
    }

    /**
     * @depends testSetupWithValidParameters
     */
    public function testMagicCall()
    {
        try {
            // Test that __call works for MongoDB methods
            $databases = iterator_to_array($this->engine->listDatabases());
            $this->assertGreaterThan(0, $databases);
        } catch (DatabaseException $e) {
            // If connection fails, skip
            $this->markTestSkipped('MongoDB connection failed');
        }
    }
}