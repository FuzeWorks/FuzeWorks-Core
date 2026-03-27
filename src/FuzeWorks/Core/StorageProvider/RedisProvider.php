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

namespace FuzeWorks\Core\StorageProvider;
use FuzeWorks\Core\Exception\StorageException;
use FuzeWorks\Core\iStorageProvider;
use Redis;
use RedisException;

class RedisProvider implements iStorageProvider
{

    /**
     * @var Redis
     */
    protected Redis $conn;

    /**
     * Initializes the RedisProvider, by connecting to the Redis Server.
     *
     * $providerConfig can contain the following keys:
     * 'socket_type', being either 'socket' or 'tcp'
     * 'socket', when socket_type is socket, contains a handle for the socket
     * 'host', when socket_type is tcp, contains the hostname or ip of the server
     * 'port', when socket_type is tcp, contains the port of the server
     * 'timeout', when socket_type is tcp, contains the amount of time to attempt connecting before failure
     * 'db_index', the specific database number to use on the Redis Server
     *
     * Throws any StorageException on failure.
     *
     * @param array $providerConfig
     * @return bool
     * @throws StorageException
     */
    public function init(array $providerConfig): bool
    {
        try {
            $this->conn = new Redis();

            // Afterwards we attempt to connect to the server
            $socketType = $providerConfig['socket_type'];
            if ($socketType === 'unix')
                $success = $this->conn->connect($providerConfig['socket']);
            elseif ($socketType === 'tcp')
                $success = $this->conn->connect($providerConfig['host'], $providerConfig['port'], $providerConfig['timeout']);
            else
                $success = false;

            // If failed, throw an exception informing so
            if (!$success)
                throw new StorageException("Could not load RedisProvider Storage. Unable to connect to server.");

            // If authentication is required, attempt to do so with the provided password
            if (isset($providerConfig['password']) && !$this->conn->auth($providerConfig['password']))
                throw new StorageException("Could not load RedisProvider Storage. Authentication failure.");

            // If a db_index is provided, use that one accordingly
            if (isset($providerConfig['db_index']) && is_int($providerConfig['db_index']))
                $this->conn->select($providerConfig['db_index']);

            // And if all goes well, report a true
            return true;

            // If any sort of failure has occurred along the way,
        } catch (RedisException $e) {
            throw new StorageException("Could not load RedisProvider Storage. RedisException thrown: '" . $e->getMessage() . "'");
        }
    }

    public function getIndex(): array
    {
        return $this->conn->sMembers('StorageIndex');
    }

    public function getItem(string $key)
    {
        // If the requested key is not part of the index, this item is not tracked and should therefore
        // return null.
        if (!$this->conn->sIsMember('StorageIndex', $key))
            return null;

        // If the data doesn't exist, return null
        if (!$this->conn->hExists('fwstorage_' . $key, 'data'))
            return null;

        return unserialize($this->conn->hGet('fwstorage_' . $key, 'data'));
    }

    public function getItemMeta(string $key): ?array
    {
        // If the requested key is not part of the index, this item is not tracked and should therefore
        // return null.
        if (!$this->conn->sIsMember('StorageIndex', $key))
            return null;

        // If the data doesn't exist, return null
        if (!$this->conn->hExists('fwstorage_' . $key, 'meta'))
            return null;

        return unserialize($this->conn->hGet('fwstorage_' . $key, 'meta'));
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
        return $this->conn->sIsMember('StorageIndex', $key);
    }

    public function clear(): bool
    {
        return $this->deleteItems($this->getIndex());
    }

    public function deleteItem(string $key): bool
    {
        // If the requested key is not part of the index, this item is not tracked and should therefore
        // return null.
        if ($this->conn->sIsMember('StorageIndex', $key))
            $this->conn->sRem('StorageIndex', $key);

        if ($this->conn->exists('fwstorage_' . $key))
            $this->conn->del('fwstorage_' . $key);

        return true;
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key)
            $this->deleteItem($key);

        return true;
    }

    public function save(string $key, $value, array $metaData = []): bool
    {
        // If the requested key is not part of the index, this item is not tracked and should therefore
        // return null.
        if (!$this->conn->sIsMember('StorageIndex', $key))
            $this->conn->sAdd('StorageIndex', $key);

        // Write to the hash
        $this->conn->hSet('fwstorage_' . $key, 'data', serialize($value));
        $this->conn->hSet('fwstorage_' . $key, 'meta', serialize($metaData));

        return true;
    }
}