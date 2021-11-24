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
use FuzeWorks\Events;
use FuzeWorks\Priority;

/**
 * Class EventTest.
 *
 * This test will test the Event class
 * @coversDefaultClass \FuzeWorks\Event
 */
class eventTest extends CoreTestAbstract
{

    /**
     * @coversNothing
     */
    public function testFireEvent()
    {
        $event = Events::fireEvent('testEvent');
        
        $this->assertInstanceOf('FuzeWorks\Event', $event);
    }

    /**
     * @depends testFireEvent
     * @covers ::isCancelled
     * @covers ::setCancelled
     */
    public function testCancelEvent()
    {
        Events::addListener(array($this, 'listener_cancel'), 'testCancelEvent', Priority::NORMAL);
        $event = Events::fireEvent('testCancelEvent');

        $this->assertTrue($event->isCancelled());
    }

    /**
     * @depends testCancelEvent
     * @covers ::setCancelled
     * @covers ::isCancelled
     */
    public function testUncancelEvent()
    {
        Events::addListener(array($this, 'listener_cancel'), 'testUncancelEvent', Priority::HIGH);
        Events::addListener(array($this, 'listener_uncancel'), 'testUncancelEvent', Priority::LOW);
        $event = Events::fireEvent('testUncancelEvent');

        $this->assertFalse($event->isCancelled());
    }

    public function listener_cancel($event)
    {
        $event->setCancelled(true);
        return $event;
    }

    public function listener_uncancel($event)
    {
        $this->assertTrue($event->isCancelled());
        $event->setCancelled(false);
        return $event;
    }
}
