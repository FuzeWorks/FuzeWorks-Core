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

namespace FuzeWorks\Core;

use FuzeWorks\Core\Event\ModelGetEvent;
use FuzeWorks\Core\Exception\ModelException;
use FuzeWorks\Core\Exception\NotFoundException;
use FuzeWorks\Core\Exception\EventException;

/**
 * Models Class.
 *
 * Simple loader class for MVC Models.
 * Typically, loads models from Application\Model unless otherwise specified.
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class Models
{
    use ComponentPathsTrait;

    /**
     * Get a model.
     *
     * Supply the name and the model will be loaded from the supplied directory,
     * or from one of the modelPaths (which you can add).
     *
     * @param string        $modelName      Name of the model
     * @param array         $modelPaths     Alternative paths to use to load the model
     * @param string        $namespace      Alternative namespace for the model. Defaults to \Application\Model
     * @param mixed         %arguments,...  Arguments to be provided to the constructor [...]
     * @return Model
     * @throws NotFoundException
     * @throws ModelException
     */
    public function get(string $modelName, array $modelPaths = [], string $namespace = '\Application\Model\\'): Model
    {
        if (empty($modelName))
            throw new ModelException("Could not load model. No name provided", 1);

        // First get the directories where the model can be located
        $modelPaths = (empty($modelPaths) ? $this->componentPaths : [3 => $modelPaths]);

        // Get arguments for constructor
        $arguments = func_num_args() > 3 ? array_slice(func_get_args(),3) : [];

        // Fire a model load event
        try {
            /** @var ModelGetEvent $event */
            $event = Events::fireEvent(new ModelGetEvent(), $modelName, $modelPaths, $namespace, $arguments);
        } catch (EventException $e) {
            throw new ModelException("Could not load model. modelGetEvent threw exception: '".$e->getMessage()."'");
        }

        // If the event is cancelled, stop loading
        if ($event->isCancelled())
            throw new ModelException("Could not load model. Model cancelled by modelGetEvent.");

        // And attempt to load the model
        return $this->loadModel($event->modelName, $event->modelPaths, $event->namespace, $event->arguments);
    }

    /**
     * Load and return a model.
     *
     * Supply the name and the model will be loaded from one of the supplied directories
     *
     * @param string $modelName Name of the model
     * @param array $modelPaths
     * @param string $namespace
     * @param array $arguments
     * @return Model                The Model object
     * @throws ModelException
     * @throws NotFoundException
     */
    protected function loadModel(string $modelName, array $modelPaths, string $namespace, array $arguments): Model
    {
        // Now figure out the className and subdir
        $class = trim($modelName, '/');
        if (($last_slash = strrpos($class, '/')) !== FALSE) {
            // Extract the path
            $subdir = substr($class, 0, ++$last_slash);

            // Get the filename from the path
            $class = substr($class, $last_slash);
        } else {
            $subdir = '';
        }

        // If the class already exists, return a new instance directly
        $class = ucfirst($class);
        $className = $namespace . $class . 'Model';
        if (class_exists($className, true)) {
            $model = new $className(...$arguments);
            if (!$model instanceof Model)
                throw new ModelException("Could not load model. Provided modelName is not instance of \FuzeWorks\Model");

            return $model;
        }

        // Search for the model file
        for ($i=Priority::getHighestPriority(); $i<=Priority::getLowestPriority(); $i++)
        {
            if (!isset($modelPaths[$i]))
                continue;

            foreach ($modelPaths[$i] as $directory) {

                // Determine the file
                $file = $directory . DS . $subdir . "model." . strtolower($class) . '.php';

                // If it doesn't, try and load the file
                if (file_exists($file)) {
                    include_once($file);

                    // Test if provided class is instance of Model
                    $model = new $className(...$arguments);
                    if (!$model instanceof Model)
                        throw new ModelException("Could not load model. Provided modelName is not instance of \FuzeWorks\Model");

                    return $model;
                }
            }
        }

        // Maybe it's in a subdirectory with the same name as the class
        if ($subdir === '') {
            return $this->loadModel($class . "/" . $class, $modelPaths, $namespace, $arguments);
        }

        throw new NotFoundException("Could not load model. Model was not found", 1);
    }
}