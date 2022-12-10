<?php
/**
 * FuzeWorks Storage Component.
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
 * @version Version 1.4.0
 */

namespace FuzeWorks;
use FuzeWorks\Exception\ConfigException;
use FuzeWorks\Exception\FactoryException;
use FuzeWorks\Exception\StorageException;
use FuzeWorks\Storage\iStorageProvider;
use FuzeWorks\Storage\StorageCache;
use Psr\SimpleCache\CacheInterface;

/**
 * Storage Class.
 *
 * This class doesn't do very much, except show the FuzeWorks\Configurator that this dependency has been loaded.
 *
 * @author    i15
 * @copyright Copyright (c) 2013 - 2020, i15. (https://i15.nl)
 */
class Storage
{

    /**
     * The configuration file for Storage
     *
     * @var array
     */
    private array $cfg;

    /**
     * The currently selected StorageProvider
     *
     * @var iStorageProvider
     */
    private iStorageProvider $provider;

    /**
     * The currently used CacheInterface
     *
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * Fetches and returns the currently selected StorageProvider
     *
     * @return iStorageProvider
     *@throws StorageException
     */
    public function getStorage(): iStorageProvider
    {
        // If the provider is already loaded, return that one
        if (isset($this->provider))
            return $this->provider;

        // Load the config, if it isn't loaded yet
        if (!isset($this->cfg))
        {
            try {
                /** @var Config $configs */
                $configs = Factory::getInstance('config');
                $this->cfg = $configs->getConfig('storage')->toArray();
            } catch (ConfigException | FactoryException $e) {
                throw new StorageException("Could not get StorageProvider. No config file named 'config.storage.php' could be found.");
            }
        }

        // Get the currently selected StorageProvider
        $selected = $this->cfg['StorageProvider'];
        if (is_null($selected))
            throw new StorageException("Could not get StorageProvider. Selected provider is null!");

        // Try and load the StorageProvider
        $class = '\FuzeWorks\Storage\Provider\\' . $selected;
        if (!class_exists($class, true))
            throw new StorageException("Could not get StorageProvider. Selected provider '".$selected."' is not recognized.");

        /** @var iStorageProvider $provider */
        $provider = new $class();
        if (!$provider instanceof iStorageProvider)
            throw new StorageException("Could not get StorageProvider. Selected provider '".$selected."' is not an instance of iStorageProvider'.");

        // Fetch the parameters
        $params = isset($this->cfg[$selected]) && is_array($this->cfg[$selected]) ? $this->cfg[$selected] : [];
        if (!$provider->init($params))
            throw new StorageException("Could not get StorageProvider. Selected provider '".$selected."' failed to load.");

        $this->provider = $provider;
        return $this->provider;
    }

    /**
     * Returns a PSR compatible Cache object
     *
     * @return CacheInterface
     * @throws StorageException
     */
    public function getCache(): CacheInterface
    {
        if (isset($this->cache))
            return $this->cache;

        $storageProvider = $this->getStorage();
        $this->cache = new StorageCache($storageProvider);

        return $this->cache;
    }
}