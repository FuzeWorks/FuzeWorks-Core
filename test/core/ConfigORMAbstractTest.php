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
 * @since Version 1.2.0
 *
 * @version Version 1.2.0
 */

use FuzeWorks\Core\ConfigORM\ConfigORMAbstract;

/**
 * Class ConfigORMAbstractTest
 *
 * Config testing suite, will test the special methods from ConfigORMAbstract
 * @coversDefaultClass \FuzeWorks\Core\ConfigORM\ConfigORMAbstract
 */
class ConfigORMAbstractTest extends CoreTestAbstract
{
    /**
     * @covers ::revert
     */
    public function testRevert()
    {
        // First load the Mock ORM
        $cfgORM = new ConfigORMMock();

        // Assert initial values
        $this->assertEquals('value', $cfgORM->initial);

        // Assert when set and changed
        $cfgORM->other = 'nothing';
        $cfgORM->initial = 'otherValue';
        $this->assertEquals('nothing', $cfgORM->other);
        $this->assertEquals('otherValue', $cfgORM->initial);

        // Revert it
        $cfgORM->revert();

        // Assert not set
        $this->assertFalse(isset($cfgORM->other));
        $this->assertEquals('value', $cfgORM->initial);
    }

    /**
     * @covers ::replace
     */
    public function testReplaceWithNewValues()
    {
        // First load the Mock ORM
        $cfgORM = new ConfigORMMock();

        // Assert initial values
        $this->assertEquals('value', $cfgORM->initial);

        // Create replacement array
        $replace = ['something' => 'wonderful', 'anything' => 'else'];

        // Assert not yet set
        $this->assertFalse(isset($cfgORM->something));
        $this->assertFalse(isset($cfgORM->anything));

        // Replace it
        $cfgORM->replace($replace);

        // Assert now set
        $this->assertEquals('value', $cfgORM->initial);
        $this->assertEquals('wonderful', $cfgORM->something);
        $this->assertEquals('else', $cfgORM->anything);
    }

    /**
     * @depends testReplaceWithNewValues
     * @covers ::replace
     */
    public function testReplaceExistingValues()
    {
        // First load the Mock ORM
        $cfgORM = new ConfigORMMock();

        // Assert initial values
        $this->assertEquals('value', $cfgORM->initial);

        // Create replacement array
        $replace = ['initial' => 'otherValue', 'something' => 'wonderful', 'anything' => 'else'];

        // Assert not yet set
        $this->assertFalse(isset($cfgORM->something));
        $this->assertFalse(isset($cfgORM->anything));
        $this->assertNotEquals('otherValue', $cfgORM->initial);

        // Replace it
        $cfgORM->replace($replace);

        // Assert now set
        $this->assertEquals('otherValue', $cfgORM->initial);
        $this->assertEquals('wonderful', $cfgORM->something);
        $this->assertEquals('else', $cfgORM->anything);
    }

    /**
     * @covers ::toArray
     */
    public function testToArray()
    {
        // First load the Mock ORM
        $cfgORM = new ConfigORMMock();

        // Assert ORM is an object
        $this->assertIsObject($cfgORM);

        // Assert output of toArray is an array
        $this->assertIsArray($cfgORM->toArray());

        // Assert array is as expected
        $this->assertEquals(['initial' => 'value'], $cfgORM->toArray());
    }

    /**
     * @depends testToArray
     * @covers ::clear
     */
    public function testClear()
    {
        // First load the Mock ORM
        $cfgORM = new ConfigORMMock();

        // Assert initial values
        $this->assertEquals('value', $cfgORM->initial);

        // Clear the cfg
        $cfgORM->clear();

        // Assert not set and empty
        $this->assertFalse(isset($cfgORM->initial));
        $this->assertEmpty($cfgORM->toArray());
    }
}

class ConfigORMMock extends ConfigORMAbstract
{
    public function __construct()
    {
        $this->originalCfg['initial'] = 'value';
        $this->cfg['initial'] = 'value';
    }
}
