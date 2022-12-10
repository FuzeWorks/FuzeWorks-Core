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
use FuzeWorks\Storage\iStorageProvider;

class DummyProvider implements iStorageProvider
{

    private array $data = ['index' => [], 'data' => []];

    public function init(array $providerConfig): bool
    {
        return true;
    }

    public function getIndex(): array
    {
        return $this->data['index'];
    }

    public function getItem(string $key)
    {
        if (!in_array($key, $this->data['index']))
            return null;

        return $this->data['data'][$key]['data'];
    }

    public function getItemMeta(string $key): ?array
    {
        if (!in_array($key, $this->data['index']))
            return null;

        return $this->data['data'][$key]['meta'];
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
        return in_array($key, $this->data['index']);
    }

    public function clear(): bool
    {
        return $this->deleteItems($this->getIndex());
    }

    public function deleteItem(string $key): bool
    {
        // Remove the index
        if (($k = array_search($key, $this->data['index'])) !== false) {
            unset($this->data['index'][$k]);
        }

        // And remove the data
        unset($this->data['data'][$key]);
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
        if (!in_array($key, $this->data['index']))
            $this->data['index'][] = $key;

        $this->data['data'][$key] = ['data' => $value, 'meta' => $metaData];
        return true;
    }
}