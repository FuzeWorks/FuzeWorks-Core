<?php
/**
 * FuzeWorks ObjectStorage Component.
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
 * @version Version 1.3.0
 */

namespace FuzeWorks\Core;
use FuzeWorks\Core\Exception\ConfigException;
use FuzeWorks\Core\Exception\FactoryException;
use FuzeWorks\Core\Exception\StorageException;
use Psr\SimpleCache\CacheInterface;

/**
 * Storage Class.
 *
 * @author    i15
 * @copyright Copyright (c) 2013 - 2020, i15. (https://i15.nl)
 */
class Storage 
{

    /**
     * The Config component 
     *
     * @var Config
     */
    protected Config $config;

    /**
     * The currently used CacheInterface
     *
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * Array of all storage provider connections
     * @var iStorageProvider[]
     */
    protected array $storage_connections = [];

    /**
     * Fetches and returns the currently selected StorageProvider
     *
     * @throws StorageException
     * @return iStorageProvider
     */
    public function getStorage(?string $storageProvider = null): iStorageProvider
    {
        // Load Config if it isn't yet loaded
        if (!isset($this->config))
            $this->config = Factory::getInstance()->config;


        $cfg = $this->config->getConfig("storage")->toArray();
        
        // Load the config, if it isn't loaded yet
        /*if (!isset($this->cfg))
        {
            try {
                $configs = Factory::getInstance('config');
                $this->cfg = $configs->getConfig('storage')->toArray();
            } catch (ConfigException | FactoryException $e) {
                throw new StorageException("Could not get StorageProvider. No config file named 'config.storage.php' could be found.");
            }
        }*/

        // Get the provided storageProvider, or the one defined in Config
        $selected = $storageProvider !== null ? $storageProvider : $cfg['StorageProvider'];

        if (is_null($selected))
            throw new StorageException("Could not get StorageProvider. Selected provider is null!");

        // If the connection is already loaded, return it directly
        if (isset($this->storage_connections[$selected]))
            return $this->storage_connections[$selected];

        // Try and load the StorageProvider
        $class = '\FuzeWorks\Core\StorageProvider\\' . $selected;
        if (!class_exists($class, true))
            throw new StorageException("Could not get StorageProvider. Selected provider '".$selected."' is not recognized.");

        /** @var iStorageProvider $provider */
        $provider = new $class();
        if (!$provider instanceof iStorageProvider)
            throw new StorageException("Could not get StorageProvider. Selected provider '".$selected."' is not an instance of iStorageProvider'.");

        // Fetch the parameters
        $params = isset($cfg[$selected]) && is_array($cfg[$selected]) ? $cfg[$selected] : [];
        if (!$provider->init($params))
            throw new StorageException("Could not get StorageProvider. Selected provider '".$selected."' failed to load.");

        // Set and return
        $this->storage_connections[$selected] = $provider;
        return $this->storage_connections[$selected];
    }

    /**
     * Returns a PSR compatible Cache object
     *
     * @return CacheInterface
     * @throws StorageException
     */
    public function getCache(?string $storageProvider = null): CacheInterface
    {
        if (isset($this->cache))
            return $this->cache;

        $storageProvider = $this->getStorage($storageProvider);
        $this->cache = new Cache($storageProvider);

        return $this->cache;
    }
}