<?php
/**
 * FuzeWorks Framework MVCR Component.
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
 * @version Version 1.3.3
 */

namespace FuzeWorks\Core\Event;
use FuzeWorks\Core\Event;

/**
 * Event that gets loaded when a model is loaded.
 *
 * Use this to cancel the loading of a model, or change the model to be loaded
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */

class ModelGetEvent extends Event
{
    /**
     * The directories the model can get loaded from.
     *
     * @var array
     */
    public array $modelPaths = [];

    /**
     * The name of the model to be loaded.
     *
     * @var string|null
     */
    public ?string $modelName = null;

    /**
     * The namespace of the model to be loaded. Defaults to Application\Model
     *
     * @var string
     */
    public string $namespace = '\Application\Model\\';

    /**
     * Arguments provided to the constructor
     *
     * @var array
     */
    public array $arguments = [];

    public function init($modelName, $modelPaths, $namespace, $arguments)
    {
        $this->modelName = $modelName;
        $this->modelPaths = $modelPaths;
        $this->namespace = $namespace;
        $this->arguments = $arguments;
    }

}