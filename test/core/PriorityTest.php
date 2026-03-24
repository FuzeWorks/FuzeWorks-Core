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
 * @since Version 1.0.4
 *
 * @version Version 1.3.0
 */

use FuzeWorks\Core\Priority;

/**
 * Class priorityTest.
 *
 * This test will test the Priority class
 * @coversDefaultClass \FuzeWorks\Core\Priority
 */
class PriorityTest extends CoreTestAbstract
{

    /**
     * @coversNothing
     */
    public function testPriorities()
    {
        $this->assertEquals(Priority::LOWEST, 5);
        $this->assertEquals(Priority::LOW, 4);
        $this->assertEquals(Priority::NORMAL, 3);
        $this->assertEquals(Priority::HIGH, 2);
        $this->assertEquals(Priority::HIGHEST, 1);
        $this->assertEquals(Priority::MONITOR, 0);
    }

    /**
     * @covers ::getPriority
     */
    public function testGetPriority()
    {
        $this->assertEquals('Priority::LOWEST', Priority::getPriority(5));
        $this->assertEquals('Priority::LOW', Priority::getPriority(4));
        $this->assertEquals('Priority::NORMAL', Priority::getPriority(3));
        $this->assertEquals('Priority::HIGH', Priority::getPriority(2));
        $this->assertEquals('Priority::HIGHEST', Priority::getPriority(1));
        $this->assertEquals('Priority::MONITOR', Priority::getPriority(0));
    }

    /**
     * @covers ::getPriority
     */
    public function testGetInvalidPriority()
    {
        $this->assertFalse(Priority::getPriority(99));
    }

    /**
     * @covers ::getHighestPriority
     */
    public function testHighestPriority()
    {
        $this->assertEquals(Priority::MONITOR, Priority::getHighestPriority());
    }

    /**
     * @covers ::getLowestPriority
     */
    public function testLowestPriority()
    {
        $this->assertEquals(Priority::LOWEST, Priority::getLowestPriority());
    }

}
