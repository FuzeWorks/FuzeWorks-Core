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

namespace FuzeWorks\Storage\Provider;
use FuzeWorks\Core;
use FuzeWorks\Exception\StorageException;
use FuzeWorks\Storage\iStorageProvider;

/**
 * FileProvider
 *
 * An ObjectStorage provider which saves objects in files using the serialize() and unserialize() functions.
 * The easiest to use StorageProvider, though with its caveats. It's nimble and works on nearly everything, but
 * not particularly fast, and has some risks when multiple processes use the data at the same time.
 *
 * @todo Figure out a way to prevent indexes from clashing in multiple threads
 * @todo Find a better way to sanitize key names other than hashing them using crc32
 * @author    i15
 * @copyright Copyright (c) 2013 - 2020, i15. (https://i15.nl)
 */
class FileProvider implements iStorageProvider
{

    /**
     * The directory where all objects are saved
     *
     * @var string
     */
    protected string $directory;

    /**
     * The file where the index for this provider is saved
     *
     * @var string
     */
    protected string $indexFile;

    /**
     * The index of all objects stored
     *
     * @var array
     */
    protected array $index;

    /**
     * @param array $providerConfig
     * @return bool
     * @throws StorageException
     */
    public function init(array $providerConfig): bool
    {
        // First load the directory from the providerConfig
        $directory = $providerConfig['storage_directory'] ?? null;

        // Check if the directory exists
        if (!file_exists($directory) || !is_dir($directory))
            throw new StorageException("Could not load FileProvider Storage. Provided storageDirectory is not a directory.");

        // Check if that directory also is writeable
        if (!Core::isReallyWritable($directory))
            throw new StorageException("Could not load FileProvider Storage. Provided storageDirectory is not writeable.");

        // Save the directory and indexFile, and load or initialize the indexFile
        $this->directory = rtrim($directory);
        $this->indexFile = $this->directory . DS . 'index.fwstorage';

        // If the index does not exist yet, load it
        if (!file_exists($this->indexFile))
        {
            if (!$this->write_file($this->indexFile, serialize(['index' => []])))
                throw new StorageException("Could not load FileProvider Storage. Could not write index.");

            chmod($this->indexFile, 0640);
        }

        // And finally, load the index
        $this->index = unserialize(file_get_contents($this->indexFile))['index'];

        return true;
    }

    public function getIndex(): array
    {
        $out = [];
        foreach ($this->index as $key => $val)
            $out[] = $key;

        return $out;
    }

    public function getItem(string $key)
    {
        // Convert the key
        $file = $this->directory . DS . crc32($key) . '.fwstorage';

        // If the key could not be found in the index, return null
        if (!isset($this->index[$key]))
            return null;

        // Check if the file exists. If not, delete the indexed value
        if (!file_exists($file))
        {
            $this->deleteItem($key);
            return null;
        }

        // Otherwise try and load the metaData and contents
        return unserialize(file_get_contents($file));
    }

    public function getItemMeta(string $key): ?array
    {
        // If the key could not be found in the index, return null
        if (!isset($this->index[$key]))
            return null;

        // Otherwise return the meta data
        return $this->index[$key]['meta'];
    }

    public function getItems(array $keys = []): array
    {
        $output = [];
        foreach ($keys as $key)
            $output[$key] = $this->getItem($key);

        return $output;
    }

    public function hasItem(string $key): bool
    {
        return isset($this->index[$key]);
    }

    public function clear(): bool
    {
        $keys = array_keys($this->index);
        return $this->deleteItems($keys);
    }

    public function deleteItem(string $key): bool
    {
        // Convert the key
        $file = $this->directory . DS . crc32($key) . '.fwstorage';

        // Remove the file first
        if (file_exists($file))
            unlink($file);

        // And remove it from the index
        if (isset($this->index[$key]))
            unset($this->index[$key]);

        // And commit the index
        return $this->commitIndex();
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key)
            $this->deleteItem($key);

        return true;
    }

    public function save(string $key, $value, array $metaData = []): bool
    {
        // Convert the key
        $file = $this->directory . DS . crc32($key) . '.fwstorage';

        // Remove the file if it already exists
        if (file_exists($file))
            unlink($file);

        // Write everything to the index
        $this->index[$key] = ['meta' => $metaData];
        $this->commitIndex();

        // And write the contents
        if ($this->write_file($file, serialize($value))) {
            chmod($file, 0640);
            return true;
        }

        return false;
    }

    private function commitIndex(): bool
    {
        if ($this->write_file($this->indexFile, serialize(['index' => $this->index]))) {
            chmod($this->indexFile, 0640);
            return true;
        }

        return false;
    }

    /**
     * Write File
     *
     * Writes data to the file specified in the path.
     * Creates a new file if non-existent.
     *
     * @param	string	$path	File path
     * @param	string	$data	Data to write
     * @return	bool
     */
    private function write_file(string $path, string $data): bool
    {
        if ( ! $fp = @fopen($path, 'wb'))
            return false;

        flock($fp, LOCK_EX);

        for ($result = $written = 0, $length = strlen($data); $written < $length; $written += $result)
            if (($result = fwrite($fp, substr($data, $written))) === false)
                break;

        flock($fp, LOCK_UN);
        fclose($fp);

        return is_int($result);
    }
}