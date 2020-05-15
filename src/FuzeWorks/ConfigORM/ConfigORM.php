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
 * @version Version 1.2.0
 */

namespace FuzeWorks\ConfigORM;
use FuzeWorks\Exception\ConfigException;

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
     * The current filename.
     *
     * @var string filename
     */
    private $file;

    /**
     * Load the ConfigORM file.
     *
     * @param string $file
     * @return ConfigORM
     * @throws ConfigException
     */
    public function load(string $file = ''): ConfigORM
    {
        if (empty($file))
        {
            throw new ConfigException('Could not load config file. No file provided', 1);
        }
        elseif (file_exists($file))
        {
            $this->file = $file;
            $this->cfg = (array) include $file;
            $this->originalCfg = $this->cfg;
        }
        else
        {
            throw new ConfigException('Could not load config file. Config file does not exist', 1);
        }

        return $this;
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