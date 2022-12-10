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

namespace FuzeWorks\Storage;
use Psr\SimpleCache\CacheInterface;

class StorageCache implements CacheInterface
{

    private iStorageProvider $provider;

    public function __construct(iStorageProvider $provider)
    {
        $this->provider = $provider;
    }

    private function testTTL(string $key)
    {
        $meta = $this->provider->getItemMeta('fwcache_' . $key);
        if (!is_null($meta) && $meta['ttl'] > 0 && time() > $meta['time'] + $meta['ttl'])
            $this->provider->deleteItem('fwcache_' . $key);
    }

    public function get($key, $default = null)
    {
        // Remove the item if its TTL has expired
        $this->testTTL($key);

        // Fetch the value
        $res = $this->provider->getItem('fwcache_' . $key);

        // If there is no value, return the default
        return is_null($res) ? $default : $res;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $meta = [
            'time' => time(),
            'ttl' => is_int($ttl) ? $ttl : 0
        ];

        return $this->provider->save('fwcache_' . $key, $value, $meta);
    }

    public function delete($key): bool
    {
        return $this->provider->deleteItem('fwcache_' . $key);
    }

    public function clear(): bool
    {
        // Fetch the index set
        $index = $this->provider->getIndex();
        foreach ($index as $entry)
        {
            if (substr($entry, 0, 8) === 'fwcache_')
                $this->provider->deleteItem($entry);
        }

        return true;
    }

    public function getMultiple($keys, $default = null): array
    {
        $out = [];
        foreach ($keys as $key)
        {
            $out[$key] = $this->get($key, $default);
        }

        return $out;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value)
            $this->set($key, $value, $ttl);

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key)
            $this->delete($key);

        return true;
    }

    public function has($key): bool
    {
        $this->testTTL($key);
        return $this->provider->hasItem('fwcache_' . $key);
    }
}