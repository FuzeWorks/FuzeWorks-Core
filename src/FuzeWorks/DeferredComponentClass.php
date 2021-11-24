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
 * @since Version 0.0.1
 *
 * @version Version 1.3.0
 */

namespace FuzeWorks;


class DeferredComponentClass
{
    /**
     * @var string Name of the class to be invoked
     */
    public string $componentClass;

    /**
     * @var string name of the method to be invoked
     */
    public string $method;

    /**
     * @var array arguments to invoke the method with
     */
    public array $arguments = [];

    /**
     * @var mixed return from the invoked method
     */
    protected $return;

    /**
     * @var bool Whether the method has been invoked
     */
    protected bool $invoked = false;

    /**
     * @var callable A callback to call when method has been invoked.
     */
    protected $callback;

    public function __construct(string $componentClass, string $method, array $arguments, callable $callback = null)
    {
        $this->componentClass = $componentClass;
        $this->method = $method;
        $this->arguments = $arguments;
        $this->callback = $callback;
    }

    /**
     * Receives the result after being invoked. Used to save the result and send back with callable if configured.
     *
     * @param $result
     */
    public function invoke($result)
    {
        $this->return = $result;
        $this->invoked = true;
        if (is_callable($this->callback))
            call_user_func($this->callback, $result);
    }

    public function isInvoked(): bool
    {
        return $this->invoked;
    }

    public function getResult()
    {
        if ($this->invoked == true)
            return $this->return;
        else
            return false;
    }


}