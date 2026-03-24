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
 * @since Version 1.1.4
 *
 * @version Version 1.3.0
 */

namespace FuzeWorks\Core\Event;
use FuzeWorks\Core\Event;

/**
 * Event that will get fired when a plugin is retrieved. 
 *
 * @author    TechFuze <contact@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class PluginGetEvent extends Event
{

    /**
     * The name of the plugin that should be loaded
     *
     * @var string
     */
	public string $pluginName;

    /**
     * Potential plugin to return instead. If set, the plugins class will return this object 
     *
     * @var object|null
     */	
	public ?object $plugin = null;

	public function init($pluginName)
	{
		$this->pluginName = $pluginName;
	}

    /**
     * Allows listeners to set a plugin that will be returned instead.
     *
     * @param object $plugin
     */	
	public function setPlugin(object $plugin)
	{
		$this->plugin = $plugin;
	}

    /**
     * Plugin that will be returned if set by a listener. 
     *
     * @return object|null $plugin
     */	
	public function getPlugin(): ?object
    {
		return $this->plugin;
	}
}

