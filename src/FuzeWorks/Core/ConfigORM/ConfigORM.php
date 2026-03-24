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

namespace FuzeWorks\Core\ConfigORM;
use FuzeWorks\Core\Exception\ConfigException;
use FuzeWorks\Core\Priority;

/**
 * ORM class for config files in PHP files.
 *
 * Handles entries in the config directory of FuzeWorks and is able to dynamically update them when requested
 *
 * @author    TechFuze <contact@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class ConfigORM extends ConfigORMAbstract
{
    /**
     * The path to the highest priority filename.
     *
     * @var string filename
     */
    protected string $file;

    /**
     * Files the ConfigORM is built on
     *
     * @var array files
     */
    protected array $files = [];

    /**
     * Whether the ConfigORM is loaded or not.
     *
     * @var bool
     */
    public bool $loaded = false;

    public function addFile(int $priority, string $file)
    {
        if (!isset($this->files[$priority]))
            $this->files[$priority] = [];

        $this->files[$priority][] = $file;
    }

    public function init()
    {
        // Set cfg
        $this->cfg = [];

        for ($i = Priority::getLowestPriority(); $i >= Priority::getHighestPriority(); $i--) {

            // If priority does not exist for this file, skip it
            if (!isset($this->files[$i]))
                continue;

            // Pass over each file in this priority
            foreach ($this->files[$i] as $file) {
                // Read the contents
                $contents = (array) include $file;

                // Merge them with the config as we know it
                $this->cfg = array_replace_recursive($this->cfg, $contents);

                // And save the last file that we found (with the highest priority)
                $this->file = $file;
                $this->loaded = true;
            }
        }

        // When done, save originalCfg
        $this->originalCfg = $this->cfg;
    }

    /**
     * Updates the config file and writes it.
     *
     * @throws ConfigException on fatal error
     */
    public function commit(): bool
    {
        // If config has a lock file, don't write
        if (isset($this->cfg['lock']))
            throw new ConfigException("Could not write config file. $this->file is locked with the 'lock' key.");

    	// Write the changes
        if (is_writable($this->file)) {
            $config = var_export($this->cfg, true);
            file_put_contents($this->file, "<?php return $config ;");

            return true;
        }
        throw new ConfigException("Could not write config file. $this->file is not writable", 1);
    }
}